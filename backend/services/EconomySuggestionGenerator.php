<?php
/**
 * backend/services/EconomySuggestionGenerator.php
 * 
 * Serviço para gerar sugestões de economia via Google Gemini API
 * Detecta categorias em alerta (80%+ do limite) ou comportamento absurdo
 * Gera sugestões apenas 1x por categoria/mês e salva no banco
 */

class EconomySuggestionGenerator {

    // Valores padrão por categoria (senso comum)
    // Usados quando categoria não tem limite definido
    private const DEFAULT_LIMITS = [
        'Alimentação' => 500,
        'Delivery' => 300,
        'Transporte' => 350,
        'Vestuário e Acessórios' => 150,
        'Entretenimento' => 200,
        'Saúde' => 200,
        'Utilidades Domésticas' => 100,
        'Educação' => 200,
        'Lazer' => 250,
        'Outros Gastos' => 100,
    ];

    // Categorias consideradas "frívolas" (alerta mesmo sem limite)
    private const FRIVOLOUS_CATEGORIES = [
        'delivery',
        'entretenimento',
        'vestuário',
        'vestuário e acessórios',
        'lazer',
        'viagens',
    ];

    private $conexao;
    private $gemini_key;

    public function __construct($conexao, $gemini_key) {
        $this->conexao = $conexao;
        $this->gemini_key = $gemini_key;
    }

    /**
     * Analisa despesas do mês e gera sugestões de economia
     * 
     * @param int $usuario_id ID do usuário
     * @param int $mes Mês (1-12)
     * @param int $ano Ano (YYYY)
     * @return array Sugestões geradas [{ categoria, tipo, mensagem, acoes }]
     */
    public function analisarEGerarSugestoes(int $usuario_id, int $mes, int $ano): array {
        $sugestoes_geradas = [];

        // 1. Buscar despesas do mês por categoria
        $categorias_alerta = $this->detectarAlertasCategorias($usuario_id, $mes, $ano);

        if (empty($categorias_alerta)) {
            return [];
        }

        // 2. Para cada categoria em alerta, verificar se já tem sugestão no banco
        foreach ($categorias_alerta as $alerta) {
            $categoria_nome = $alerta['categoria'];
            
            // Verificar se já existe sugestão para este mês/categoria
            $ja_existe = $this->verificarSugestaoExistente(
                $usuario_id,
                $categoria_nome,
                $mes,
                $ano
            );

            if ($ja_existe) {
                // Reusar sugestão existente
                $sugestoes_geradas[] = $ja_existe;
                continue;
            }

            // 3. Gerar nova sugestão via Gemini
            $sugestao = $this->gerarSugestaoGemini(
                $usuario_id,
                $categoria_nome,
                $alerta['gasto'],
                $alerta['limite'],
                $alerta['percentual'],
                $alerta['tipo']
            );

            if ($sugestao) {
                // Salvar no banco e obter ID
                $id_sugestao = $this->salvarSugestaoNoBanco(
                    $usuario_id,
                    $categoria_nome,
                    $mes,
                    $ano,
                    $sugestao,
                    $alerta
                );

                if ($id_sugestao) {
                    $sugestao['id'] = $id_sugestao;
                    $sugestoes_geradas[] = $sugestao;
                }
            }
        }

        return $sugestoes_geradas;
    }

    /**
     * Detecta categorias que estão em alerta (80%+ do limite ou comportamento absurdo)
     * 
     * @return array [{ categoria, gasto, limite, percentual, tipo: 'orcamento'|'comportamento' }]
     */
    private function detectarAlertasCategorias(int $usuario_id, int $mes, int $ano): array {
        $alertas = [];

        // Buscar todas as categorias com despesas neste mês
        $query = "
            SELECT 
                c.nome as categoria,
                SUM(d.valor) as total_gasto
            FROM despesas d
            JOIN categorias c ON d.categoria_id = c.id
            WHERE d.usuario_id = ? 
              AND MONTH(d.data_despesa) = ?
              AND YEAR(d.data_despesa) = ?
            GROUP BY c.id, c.nome
            ORDER BY total_gasto DESC
        ";

        $stmt = $this->conexao->prepare($query);
        $stmt->bind_param('iii', $usuario_id, $mes, $ano);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $categoria = $row['categoria'];
            $gasto = (float) $row['total_gasto'];

            // 1. Buscar limite definido pelo usuário (se existir)
            $limite = $this->buscarLimiteDefinido($usuario_id, $categoria, $mes, $ano);

            // 2. Se não tem limite, usar valor padrão
            if (!$limite) {
                $limite = $this->getDefaultLimit($categoria);
            }

            // 3. Calcular percentual
            $percentual = ($gasto / $limite) * 100;

            // 4. Verificar alertas
            if ($percentual >= 80) {
                // Limite ultrapassado ou quase (orcamento)
                $alertas[] = [
                    'categoria' => $categoria,
                    'gasto' => $gasto,
                    'limite' => $limite,
                    'percentual' => $percentual,
                    'tipo' => 'orcamento',
                ];
            } elseif ($this->ehCategoriaFrivola($categoria) && $gasto > $limite) {
                // Categoria frívola com gasto acima do default (comportamento absurdo)
                $alertas[] = [
                    'categoria' => $categoria,
                    'gasto' => $gasto,
                    'limite' => $limite,
                    'percentual' => $percentual,
                    'tipo' => 'comportamento',
                ];
            }
        }

        $stmt->close();
        return $alertas;
    }

    /**
     * Buscar limite definido para uma categoria em um mês/ano
     */
    private function buscarLimiteDefinido(int $usuario_id, string $categoria, int $mes, int $ano): ?float {
        $query = "
            SELECT limite_mensal
            FROM orcamento_categorias
            WHERE usuario_id = ?
              AND categoria_nome = ?
              AND mes = ?
              AND ano = ?
            LIMIT 1
        ";

        $stmt = $this->conexao->prepare($query);
        $stmt->bind_param('isii', $usuario_id, $categoria, $mes, $ano);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return (float) $row['limite_mensal'];
        }

        $stmt->close();
        return null;
    }

    /**
     * Obter limite padrão para uma categoria
     */
    private function getDefaultLimit(string $categoria): float {
        $categoria_lower = strtolower($categoria);

        foreach (self::DEFAULT_LIMITS as $key => $value) {
            if (strtolower($key) === $categoria_lower || stripos($categoria_lower, strtolower($key)) !== false) {
                return (float) $value;
            }
        }

        return 100; // Fallback
    }

    /**
     * Verificar se categoria é "frívola"
     */
    private function ehCategoriaFrivola(string $categoria): bool {
        $cat_lower = strtolower($categoria);

        foreach (self::FRIVOLOUS_CATEGORIES as $friv) {
            if (stripos($cat_lower, $friv) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar se já existe sugestão para categoria/mês/ano
     * Se existir, retornar dados salvos
     */
    private function verificarSugestaoExistente(int $usuario_id, string $categoria, int $mes, int $ano): ?array {
        $query = "
            SELECT 
                id,
                titulo,
                descricao,
                fonte,
                prioridade
            FROM sugestoes_economia
            WHERE usuario_id = ?
              AND categoria_nome = ?
              AND mes = ?
              AND ano = ?
              AND status = 'pendente'
            LIMIT 1
        ";

        $stmt = $this->conexao->prepare($query);
        $stmt->bind_param('isii', $usuario_id, $categoria, $mes, $ano);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();

            // Parsear JSON da coluna 'fonte' (contém as ações em JSON)
            $acoes = json_decode($row['fonte'], true) ?? [];

            return [
                'id' => (int)$row['id'],
                'categoria' => $categoria,
                'titulo' => $row['titulo'],
                'mensagem' => $row['descricao'],
                'acoes' => $acoes,
                'prioridade' => $row['prioridade'],
            ];
        }

        $stmt->close();
        return null;
    }

    /**
     * Gerar sugestão via Google Gemini API
     */
    private function gerarSugestaoGemini(
        int $usuario_id,
        string $categoria,
        float $gasto,
        float $limite,
        float $percentual,
        string $tipo_alerta
    ): ?array {
        $prompt = $this->montarPromptSugestao($categoria, $gasto, $limite, $percentual, $tipo_alerta);

        $response = $this->chamarGeminiAPI($prompt);

        if (!$response) {
            return null;
        }

        return $response;
    }

    /**
     * Montar prompt para Gemini
     */
    private function montarPromptSugestao(
        string $categoria,
        float $gasto,
        float $limite,
        float $percentual,
        string $tipo_alerta
    ): string {
        $percentual_fmt = number_format($percentual, 1, ',', '.');
        $gasto_fmt = number_format($gasto, 2, ',', '.');
        $limite_fmt = number_format($limite, 2, ',', '.');

        $descricao_alerta = ($tipo_alerta === 'orcamento')
            ? "ultrapassou o orçamento ($percentual_fmt% do limite)"
            : "comportamento de gasto anormal (categoria fútil)";

        return <<<PROMPT
Você é um consultor financeiro do InvestAI. 

Um usuário $descricao_alerta em **{$categoria}**.

**Dados do alerta:**
- Categoria: {$categoria}
- Gasto este mês: R\$ {$gasto_fmt}
- Limite recomendado: R\$ {$limite_fmt}
- Percentual: {$percentual_fmt}%

Gere uma sugestão concisa, prática e motivadora em JSON com:
1. "titulo": Título curto (5-8 palavras)
2. "mensagem": Mensagem principal (20-40 palavras)
3. "acoes": Array de 2-3 ações específicas para reduzir gasto nesta categoria

Responda APENAS com JSON válido, sem markdown:
{"titulo":"...","mensagem":"...","acoes":["acao1","acao2","acao3"]}

Exemplo:
{"titulo":"Cuidado com as compras online","mensagem":"Você está gastando demais com compras pela internet. Tente economizar planificando antecipadamente e comparando preços.","acoes":["Compare preços em 3 lojas antes de comprar","Use cupons de desconto","Defina um orçamento semanal"]}

Agora crie a sugestão:
PROMPT;
    }

    /**
     * Chamar Google Gemini API
     */
    private function chamarGeminiAPI(string $prompt): ?array {
        if (!$this->gemini_key) {
            error_log("Gemini API Key não configurada");
            return null;
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' 
            . urlencode($this->gemini_key);

        $body = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 1024,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$response || $http_code !== 200) {
            error_log("Gemini API Error ($http_code): " . mb_substr($response ?? '', 0, 200));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $data = json_decode($response, true);
        $raw_text = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

        // Parsear JSON da resposta
        $sugestao = json_decode($raw_text, true);

        if (!$sugestao || !isset($sugestao['titulo'], $sugestao['mensagem'], $sugestao['acoes'])) {
            error_log("Resposta Gemini inválida: " . $raw_text);
            return null;
        }

        return $sugestao;
    }

    /**
     * Salvar sugestão no banco de dados
     */
    private function salvarSugestaoNoBanco(
        int $usuario_id,
        string $categoria,
        int $mes,
        int $ano,
        array $sugestao,
        array $alerta_dados
    ): ?int {
        $titulo = $sugestao['titulo'];
        $descricao = $sugestao['mensagem'];
        $acoes_json = json_encode($sugestao['acoes'], JSON_UNESCAPED_UNICODE);
        $fonte = $acoes_json;
        $prioridade = ($alerta_dados['percentual'] > 150) ? 'alta' : (($alerta_dados['percentual'] > 100) ? 'media' : 'baixa');

        $query = "
            INSERT INTO sugestoes_economia 
            (usuario_id, titulo, descricao, fonte, categoria_nome, mes, ano, prioridade, status, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())
            ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo),
            descricao = VALUES(descricao),
            fonte = VALUES(fonte),
            prioridade = VALUES(prioridade)
        ";

        $stmt = $this->conexao->prepare($query);

        if (!$stmt) {
            error_log("Prepare error: " . $this->conexao->error);
            return null;
        }

        $stmt->bind_param(
            'issssiis',
            $usuario_id,
            $titulo,
            $descricao,
            $fonte,
            $categoria,
            $mes,
            $ano,
            $prioridade
        );

        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Execute error: " . $stmt->error);
            $stmt->close();
            return null;
        }

        // Buscar o ID da sugestão (pode ser nova ou atualizada)
        $id_inserido = $stmt->insert_id;
        $stmt->close();

        // Se foi UPDATE (não INSERT), buscar o ID correto
        if ($id_inserido === 0) {
            $query_id = "
                SELECT id FROM sugestoes_economia
                WHERE usuario_id = ? AND categoria_nome = ? AND mes = ? AND ano = ?
                LIMIT 1
            ";
            $stmt = $this->conexao->prepare($query_id);
            $stmt->bind_param('isii', $usuario_id, $categoria, $mes, $ano);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row ? (int)$row['id'] : null;
        }

        return $id_inserido;
    }

    /**
     * Atualizar sugestões quando o orçamento é alterado
     * Apenas deleta a sugestão para forçar regeneração com valores novos
     * 
     * @param int $usuario_id ID do usuário
     * @param string $categoria Categoria
     * @param int $mes Mês
     * @param int $ano Ano
     * @return bool Sucesso da operação
     */
    public function atualizarSugestoesAoAlterarOrcamento(int $usuario_id, string $categoria, int $mes, int $ano): bool {
        // Deletar sugestão antiga para forçar regeneração com valores novos
        $query_delete = "
            DELETE FROM sugestoes_economia
            WHERE usuario_id = ?
              AND categoria_nome = ?
              AND mes = ?
              AND ano = ?
              AND status = 'pendente'
        ";

        $stmt = $this->conexao->prepare($query_delete);
        $stmt->bind_param('isii', $usuario_id, $categoria, $mes, $ano);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}
?>
