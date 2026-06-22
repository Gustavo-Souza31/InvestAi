<?php
/**
 * backend/ia/chat/ChatAgent.php
 *
 * Orquestrador principal do chat com IA.
 * Instanciado por backend/api/chat/mensagem.php.
 */

require_once __DIR__ . '/OllamaClient.php';
require_once __DIR__ . '/ToolHandler.php';
require_once __DIR__ . '/ResponseGenerator.php';

class ChatAgent {

    public function __construct(
        private $conexao,
        private string $model = ''
    ) {}

    /**
     * Ponto de entrada principal.
     * Retorna ['resposta' => string, 'acao' => string]
     */
    public function processar(string $mensagem, int $usuario_id, int $mes, int $ano, array $historico = []): array {
        $ollama    = new OllamaClient();
        $handler   = new ToolHandler();
        $generator = new ResponseGenerator($ollama);

        // Primeiro nome do usuário para personalizar respostas
        $nome_usuario = $this->buscarNomeUsuario($usuario_id);

        // 1. Coletar definições de todas as tools
        $definitions = $handler->getAllDefinitions($this->conexao, $usuario_id, $mes, $ano);

        // 2. Detectar confirmação de ação destrutiva pendente ANTES de chamar o modelo.
        //    Isso evita depender do modelo para interpretar "sim"/"pode" após um pedido
        //    de confirmação, que é a causa dos bugs de fluxo pós-confirmação.
        $call = $this->detectarConfirmacaoPendente($mensagem, $historico);

        if ($call === null) {
            // Classificar intenção via Ollama com saída estruturada
            $call = $ollama->callWithFunctions($mensagem, $definitions, $historico, $nome_usuario);

            if ($call === null) {
                $httpCode = $ollama->getLastHttpCode();

                if ($httpCode === 429) {
                    return ['resposta' => 'Minha cota de IA foi esgotada por hoje. Tente novamente amanhã! 🔌', 'acao' => 'conversa'];
                }
                if ($httpCode === 401 || $httpCode === 403) {
                    return ['resposta' => 'Estou com problema de acesso à IA. Avisa o suporte! 🔧', 'acao' => 'conversa'];
                }

                error_log("ChatAgent: Ollama indisponível (HTTP $httpCode), retornando conversa");
                $call = ['name' => 'conversa', 'args' => []];
            }
        }

        $toolName = $call['name'];
        $params   = $call['args'] ?? [];

        // 3. Short-circuit: pedir_confirmacao não executa ação nem 2ª chamada ao modelo
        if ($toolName === 'pedir_confirmacao') {
            $pergunta = $params['pergunta'] ?? 'Pode me dar mais detalhes? 🤔';
            return ['resposta' => $pergunta, 'acao' => 'pedir_confirmacao'];
        }

        // Short-circuit: acao_indisponivel retorna mensagem fixa sem chamar o modelo
        if ($toolName === 'acao_indisponivel') {
            return [
                'resposta' => 'Essa função ainda não está disponível. Posso ajudar com orçamentos, despesas, ganhos e consultas financeiras! 💬',
                'acao'     => 'acao_indisponivel',
            ];
        }

        // 4. Executar a tool (com validação de segurança para ações destrutivas)
        $this->validarConfirmacaoDestructiva($toolName, $params, $mensagem, $historico);
        $resultado = $handler->dispatch($toolName, $params, $this->conexao, $usuario_id, $mes, $ano);

        // 5. Gerar resposta amigável
        $resposta = $generator->generate($mensagem, $toolName, $resultado, $nome_usuario);

        return [
            'resposta'             => $resposta,
            'acao'                 => $toolName,
            'precisa_confirmacao'  => ($resultado['tipo'] ?? '') === 'precisa_confirmacao',
        ];
    }

    private const TOOLS_DESTRUCTIVAS = [
        'deletar_todas_despesas',
        'deletar_todos_ganhos',
        'deletar_todos_orcamentos',
        'deletar_despesa',
        'deletar_ganho',
        'deletar_meta',
        'deletar_todas_metas',
        'deletar_aporte',
    ];

    /**
     * Garante que ações destrutivas com confirmado=true só sejam executadas se houver
    * confirmação real do usuário no histórico. Protege contra o modelo passar confirmado=true
     * diretamente na primeira mensagem.
     */
    private function validarConfirmacaoDestructiva(string $toolName, array &$params, string $mensagem, array $historico): void {
        if (!in_array($toolName, self::TOOLS_DESTRUCTIVAS, true)) return;
        if (!($params['confirmado'] ?? false)) return;

        $msg_norm = $this->normalizarFallback($mensagem);
        $usuario_confirmou = $this->isConfirmationPositive($msg_norm);

        // Verifica se a última mensagem do assistente era uma pergunta de confirmação
        $assistente_perguntou = false;
        foreach (array_reverse($historico) as $msg) {
            if (($msg['role'] ?? '') !== 'assistente') continue;
            $t = $this->normalizarFallback($msg['texto'] ?? '');
            if (str_contains($t, 'certeza') || str_contains($t, 'confirma') || str_contains($t, 'desfeita')) {
                $assistente_perguntou = true;
            }
            break;
        }

        if (!$usuario_confirmou || !$assistente_perguntou) {
            $params['confirmado'] = false;
            error_log("ChatAgent: confirmado forçado para false em $toolName (usuario_confirmou=" . ($usuario_confirmou ? 'true' : 'false') . " assistente_perguntou=" . ($assistente_perguntou ? 'true' : 'false') . ")");
        }
    }

    /**
     * Detecta se o usuário está respondendo com confirmação a uma ação destrutiva pendente.
     * Analisa a última mensagem do assistente no histórico: se era um pedido de confirmação
     * de deleção ("não pode ser desfeita"), e a mensagem atual é uma afirmação curta,
    * retorna diretamente a chamada da tool com confirmado=true — sem precisar do modelo.
     * Retorna null se não há confirmação pendente detectada.
     */
    private function detectarConfirmacaoPendente(string $mensagem, array $historico): ?array {
        if (empty($historico)) return null;

        $msg_norm = $this->normalizarFallback($mensagem);

        // Mensagens longas provavelmente são novos pedidos, não confirmações simples
        if (mb_strlen($msg_norm) > 60) return null;

        // Negação explícita — usuário está cancelando, não confirmando
        if (preg_match('/\b(nao|cancela|esquece|para|stop|nope|nem)\b/', $msg_norm)) return null;

        // A mensagem deve conter uma palavra de confirmação positiva
        $usuario_confirmou = $this->isConfirmationPositive($msg_norm);
        if (!$usuario_confirmou) return null;

        // Busca a última mensagem do assistente no histórico
        $ultima_assistente_texto = null;
        foreach (array_reverse($historico) as $msg) {
            if (($msg['role'] ?? '') === 'assistente') {
                $ultima_assistente_texto = $msg['texto'] ?? '';
                break;
            }
        }
        if ($ultima_assistente_texto === null) return null;

        $norm_ass = $this->normalizarFallback($ultima_assistente_texto);

        // Indicador confiável de confirmação de deleção: todas as tools de delete usam
        // "Essa ação não pode ser desfeita!" — a palavra "desfeita" identifica este caso.
        if (!str_contains($norm_ass, 'desfeita')) return null;

        // Determina qual tool estava pendente pelo conteúdo da mensagem do assistente
        if (str_contains($norm_ass, 'todas') && (str_contains($norm_ass, 'despesas') || str_contains($norm_ass, 'gastos'))) {
            error_log("ChatAgent: retomando confirmação pendente → deletar_todas_despesas");
            return ['name' => 'deletar_todas_despesas', 'args' => ['confirmado' => true]];
        }
        if ((str_contains($norm_ass, 'todos') || str_contains($norm_ass, 'todas')) && (str_contains($norm_ass, 'ganhos') || str_contains($norm_ass, 'receitas'))) {
            error_log("ChatAgent: retomando confirmação pendente → deletar_todos_ganhos");
            return ['name' => 'deletar_todos_ganhos', 'args' => ['confirmado' => true]];
        }
        if ((str_contains($norm_ass, 'todos') || str_contains($norm_ass, 'todas')) && (str_contains($norm_ass, 'orcamento') || str_contains($norm_ass, 'orcamentos') || str_contains($norm_ass, 'budget'))) {
            error_log("ChatAgent: retomando confirmação pendente → deletar_todos_orcamentos");
            return ['name' => 'deletar_todos_orcamentos', 'args' => ['confirmado' => true]];
        }

        // Detecção de confirmação para metas e aportes
        if ((str_contains($norm_ass, 'todas') || str_contains($norm_ass, 'todos')) && str_contains($norm_ass, 'meta')) {
            error_log("ChatAgent: retomando confirmação pendente → deletar_todas_metas");
            return ['name' => 'deletar_todas_metas', 'args' => ['confirmado' => true]];
        }

        if (str_contains($norm_ass, 'aporte')) {
            error_log("ChatAgent: retomando confirmação pendente → deletar_aporte");
            return ['name' => 'deletar_aporte', 'args' => ['meta_nome_busca' => '', 'confirmado' => true]];
        }

        if (str_contains($norm_ass, 'meta')) {
            $nome_meta = '';
            if (preg_match('/["\u{201C}\u{201D}](.+?)["\u{201C}\u{201D}]/u', $ultima_assistente_texto, $m)) {
                $nome_meta = trim($m[1]);
            }
            error_log("ChatAgent: retomando confirmação pendente → deletar_meta nome='$nome_meta'");
            return ['name' => 'deletar_meta', 'args' => ['nome_busca' => $nome_meta, 'confirmado' => true]];
        }

        // Para delete específico: tenta extrair o nome do item entre aspas da mensagem
        // Formato esperado: ⚠️ Confirmar exclusão de "NOME" (R$ X,XX)? ...
        $descricao = '';
        if (preg_match('/["\u{201C}\u{201D}](.+?)["\u{201C}\u{201D}]/u', $ultima_assistente_texto, $m)) {
            $descricao = trim($m[1]);
        }

        // Determina se era ganho ou despesa olhando a mensagem anterior do usuário no histórico
        $tipo = 'despesa';
        $passou_assistente = false;
        foreach (array_reverse($historico) as $msg) {
            if (!$passou_assistente) {
                if (($msg['role'] ?? '') === 'assistente') $passou_assistente = true;
                continue;
            }
            if (($msg['role'] ?? '') === 'usuario') {
                $txt = $this->normalizarFallback($msg['texto'] ?? '');
                if (str_contains($txt, 'ganho') || str_contains($txt, 'receita') || str_contains($txt, 'recebiment')) {
                    $tipo = 'ganho';
                }
                break;
            }
        }

        if ($tipo === 'ganho') {
            error_log("ChatAgent: retomando confirmação pendente → deletar_ganho descricao='$descricao'");
            return ['name' => 'deletar_ganho', 'args' => ['descricao' => $descricao, 'confirmado' => true]];
        }

        error_log("ChatAgent: retomando confirmação pendente → deletar_despesa descricao='$descricao'");
        return ['name' => 'deletar_despesa', 'args' => ['descricao' => $descricao, 'confirmado' => true]];
    }

    private function isConfirmationPositive(string $msg_norm): bool {
        $msg_norm = trim(preg_replace('/\s+/', ' ', $msg_norm));
        if ($msg_norm === '') return false;

        if (preg_match('/\b(nao|n\b|nao mesmo|cancela|cancelar|esquece|para|stop|nope|nem)\b/u', $msg_norm)) {
            return false;
        }

        $frases_confirmacao = [
            'sim', 's', 'ok', 'okay', 'okey', 'certo', 'claro', 'confirmo', 'confirmado',
            'pode', 'pode sim', 'pode ser', 'pode apagar', 'pode excluir', 'pode deletar',
            'pode remover', 'pode limpar', 'ta bom', 'tudo bem', 'bora', 'manda ver',
            'vai', 'vai la', 'vai em frente', 'faz', 'faz isso', 'executa', 'apaga',
            'exclui', 'deleta', 'remove', 'limpa', 'beleza', 'fechou', 'tranquilo', 'show',
            'manda', 'prosseguir', 'continue', 'continuar'
        ];

        foreach ($frases_confirmacao as $frase) {
            if (str_contains($msg_norm, $frase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna o primeiro nome do usuário a partir do banco, ou string vazia se não encontrar.
     */
    private function buscarNomeUsuario(int $usuario_id): string {
        $stmt = $this->conexao->prepare("SELECT nome FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $nome_completo = trim($row['nome'] ?? '');
        if ($nome_completo === '') return '';

        // Retorna apenas o primeiro nome
        return explode(' ', $nome_completo)[0];
    }

    private function normalizarFallback(string $texto): string {
        $texto = mb_strtolower($texto, 'UTF-8');
        $de    = ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ'];
        $para  = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
        return str_replace($de, $para, $texto);
    }
}
