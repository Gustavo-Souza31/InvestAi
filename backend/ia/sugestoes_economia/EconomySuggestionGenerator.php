<?php
/**
 * backend/ia/sugestoes_economia/EconomySuggestionGenerator.php
 *
 * Serviço para gerar sugestões de economia via Ollama local
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
    private $ollama_url;
    private $ollama_model;

    public function __construct($conexao, $ollama_url = null, $ollama_model = null) {
        $this->conexao = $conexao;
        $this->ollama_url = rtrim($ollama_url ?: getenv('OLLAMA_URL') ?: 'http://localhost:11434', '/');
        $this->ollama_model = $ollama_model ?: getenv('OLLAMA_MODEL') ?: 'llama3.1:latest';
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

            // 3. Gerar nova sugestão via Ollama
            $sugestao = $this->gerarSugestaoOllama(
                $usuario_id,
                $categoria_nome,
                $alerta['gasto'],
                $alerta['limite'],
                $alerta['percentual'],
                $alerta['tipo']
            );

            // Se o modelo falhou, usar sugestão estática para não sumir o alerta
            if (!$sugestao) {
                $sugestao = $this->gerarSugestaoFallback($categoria_nome, $alerta['tipo']);
            }

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
                    $sugestao['id']   = $id_sugestao;
                    $sugestao['tipo'] = $alerta['tipo'];
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

            // 1. Buscar limite definido pelo usuário
            $limite_definido = $this->buscarLimiteDefinido($usuario_id, $categoria, $mes, $ano);
            $tem_limite_usuario = ($limite_definido !== null);

            // 2. Se não tem limite, usar valor padrão
            $limite = $limite_definido ?? $this->getDefaultLimit($categoria);

            // 3. Calcular percentual
            $percentual = ($gasto / $limite) * 100;

            // 4. Verificar alertas e determinar tipo
            if ($tem_limite_usuario) {
                // Categorias com orçamento definido: faixas de alerta por percentual
                if ($percentual >= 125) {
                    $tipo = 'limite_ultrapassado';
                } elseif ($percentual >= 100) {
                    $tipo = 'limite_atingido';
                } elseif ($percentual >= 75) {
                    $tipo = 'quase_no_limite';
                } else {
                    continue; // Abaixo de 75%, sem alerta
                }

                $alertas[] = [
                    'categoria' => $categoria,
                    'gasto'     => $gasto,
                    'limite'    => $limite,
                    'percentual'=> $percentual,
                    'tipo'      => $tipo,
                ];
            } else {
                // Sem orçamento definido: apenas categorias frívolas ou acima de 80% do padrão
                if ($percentual >= 80 || ($this->ehCategoriaFrivola($categoria) && $gasto > $limite)) {
                    $alertas[] = [
                        'categoria' => $categoria,
                        'gasto'     => $gasto,
                        'limite'    => $limite,
                        'percentual'=> $percentual,
                        'tipo'      => 'comportamento',
                    ];
                }
            }
        }

        $stmt->close();
        return $alertas;
    }

    /**
     * Buscar limite definido para uma categoria em um mês/ano
     */
    private function buscarLimiteDefinido(int $usuario_id, string $categoria, int $mes, int $ano): ?float {
        // Buscar primeiro o ID da categoria
        $query_cat = "
            SELECT id
            FROM categorias
            WHERE nome = ?
              AND tipo = 'despesa'
            LIMIT 1
        ";

        $stmt_cat = $this->conexao->prepare($query_cat);
        $stmt_cat->bind_param('s', $categoria);
        $stmt_cat->execute();
        $result_cat = $stmt_cat->get_result();

        if (!$row_cat = $result_cat->fetch_assoc()) {
            $stmt_cat->close();
            return null;
        }

        $categoria_id = $row_cat['id'];
        $stmt_cat->close();

        // Agora buscar o limite usando categoria_id
        $query = "
            SELECT limite_mensal
            FROM orcamento_categorias
            WHERE usuario_id = ?
              AND categoria_id = ?
              AND mes = ?
              AND ano = ?
            LIMIT 1
        ";

        $stmt = $this->conexao->prepare($query);
        $stmt->bind_param('iiii', $usuario_id, $categoria_id, $mes, $ano);
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
            SELECT id, titulo, descricao, fonte
            FROM sugestoes_economia
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

            $fonteData = json_decode($row['fonte'], true) ?? [];
            // Suporte aos dois formatos: novo {acoes, tipo} e antigo [acao1, acao2]
            if (isset($fonteData['acoes'])) {
                $acoes = $fonteData['acoes'];
                $tipo  = $fonteData['tipo'] ?? 'comportamento';
            } else {
                $acoes = $fonteData;
                $tipo  = 'comportamento';
            }

            return [
                'id'        => (int)$row['id'],
                'categoria' => $categoria,
                'titulo'    => $row['titulo'],
                'mensagem'  => $row['descricao'],
                'acoes'     => $acoes,
                'tipo'      => $tipo,
            ];
        }

        $stmt->close();
        return null;
    }

    /**
    * Gerar sugestão via Ollama local
     */
    private function gerarSugestaoOllama(
        int $usuario_id,
        string $categoria,
        float $gasto,
        float $limite,
        float $percentual,
        string $tipo_alerta
    ): ?array {
        $prompt = $this->montarPromptSugestao($categoria, $gasto, $limite, $percentual, $tipo_alerta);

        $response = $this->chamarOllamaAPI($prompt);

        if (!$response) {
            return null;
        }

        return $response;
    }

    /**
    * Montar prompt para o modelo local
     */
    private function montarPromptSugestao(
        string $categoria,
        float $gasto,
        float $limite,
        float $percentual,
        string $tipo_alerta
    ): string {
        $percentual_fmt = number_format($percentual, 1, ',', '.');
        $gasto_fmt      = number_format($gasto,      2, ',', '.');
        $limite_fmt     = number_format($limite,     2, ',', '.');

        switch ($tipo_alerta) {
            case 'quase_no_limite':
                $contexto = "já usou {$percentual_fmt}% do orçamento de {$categoria} este mês e está chegando perto do limite";
                $urgencia = "Use um tom amigável e preventivo — o usuário ainda pode frear antes de estourar.";
                break;
            case 'limite_atingido':
                $contexto = "atingiu o limite do orçamento de {$categoria} este mês ({$percentual_fmt}%)";
                $urgencia = "Use um tom direto mas encorajador — o limite chegou, hora de apertar o cinto.";
                break;
            case 'limite_ultrapassado':
                $contexto = "ultrapassou bastante o orçamento de {$categoria} este mês ({$percentual_fmt}% do limite)";
                $urgencia = "Use um tom urgente mas sem assustar — o usuário precisa agir agora.";
                break;
            case 'orcamento':
                $contexto = "ultrapassou o orçamento de {$categoria} ({$percentual_fmt}% do limite)";
                $urgencia = "Use um tom motivador e prático.";
                break;
            default: // comportamento
                $contexto = "tem gasto elevado na categoria {$categoria}, que é considerada uma categoria de consumo fútil ou sem limite definido";
                $urgencia = "Use um tom leve e educativo — o usuário pode não ter percebido o padrão.";
                break;
        }

        return <<<PROMPT
Você é um assistente financeiro descontraído do InvestAI.

Um usuário {$contexto}.

**Dados:**
- Categoria: {$categoria}
- Gasto este mês: R\$ {$gasto_fmt}
- Limite: R\$ {$limite_fmt}
- Percentual usado: {$percentual_fmt}%

**Tom:** Seja conversacional, humano e use 1 emoji relevante na mensagem. Evite linguagem robótica ou muito formal. {$urgencia}

Gere a sugestão em JSON com:
1. "titulo": Título curto e direto (5-8 palavras), pode ter 1 emoji
2. "mensagem": Mensagem principal conversacional (20-40 palavras), com 1 emoji
3. "acoes": Array de 2-3 ações práticas e específicas para esta categoria

Responda APENAS com JSON válido, sem markdown:
{"titulo":"...","mensagem":"...","acoes":["acao1","acao2","acao3"]}

Exemplo de tom esperado:
{"titulo":"Ei, o limite de Delivery tá chegando! 🚨","mensagem":"Você já usou 80% do seu orçamento de delivery este mês. Que tal cozinhar em casa nos próximos dias? 🍳","acoes":["Planeje 3 refeições caseiras esta semana","Use o que já tem na geladeira antes de pedir","Reserve delivery só para ocasiões especiais"]}

Agora crie a sugestão:
PROMPT;
    }

    /**
     * Chamar API local do Ollama
     */
    private function chamarOllamaAPI(string $prompt): ?array {
        $body = json_encode([
            'model' => $this->ollama_model,
            'stream' => false,
            'messages' => [
                ['role' => 'system', 'content' => 'Você é um assistente financeiro descontraído do InvestAI. Responda apenas com JSON válido.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'options' => [
                'temperature' => 0.3,
                'num_predict' => 1024,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($this->ollama_url . '/api/chat');
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
            error_log("Ollama API Error ($http_code): " . mb_substr($response ?? '', 0, 200));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $data = json_decode($response, true);
        $raw_text = trim($data['message']['content'] ?? '');
        if ($raw_text === '' && isset($data['response'])) {
            $raw_text = trim((string) $data['response']);
        }

        // Parsear JSON da resposta
        $sugestao = json_decode($raw_text, true);

        if (!$sugestao || !isset($sugestao['titulo'], $sugestao['mensagem'], $sugestao['acoes'])) {
            error_log("Resposta Ollama inválida: " . $raw_text);
            return null;
        }

        return $sugestao;
    }

    /**
     * Gera sugestão estática quando a IA não está disponível
     */
    private function gerarSugestaoFallback(string $categoria, string $tipo): array {
        $acoes_por_categoria = [
            'Alimentação'            => ['Planeje refeições semanais com antecedência', 'Evite delivery e prefira cozinhar em casa', 'Faça uma lista antes de ir ao mercado'],
            'Transporte'             => ['Avalie caronas compartilhadas ou transporte público', 'Abasteça no posto mais barato da região', 'Combine trajetos para reduzir viagens'],
            'Entretenimento'         => ['Prefira opções de lazer gratuitas ou de baixo custo', 'Revise assinaturas que você usa pouco', 'Estabeleça um limite semanal de gastos'],
            'Vestuário e Acessórios' => ['Espere promoções antes de comprar', 'Avalie o que já tem no guarda-roupa antes de comprar', 'Prefira peças versáteis e duráveis'],
            'Saúde'                  => ['Compare preços em diferentes farmácias', 'Verifique genéricos disponíveis para medicamentos', 'Considere planos de saúde preventivos'],
            'Educação'               => ['Busque cursos gratuitos online para complementar', 'Verifique descontos para pagamento à vista', 'Compartilhe materiais com colegas'],
            'Habitação'              => ['Revise contratos de serviços (internet, TV)', 'Reduza consumo de água e energia', 'Negocie reajustes com antecedência'],
        ];

        $acoes = $acoes_por_categoria[$categoria]
            ?? ['Revise seus gastos nesta categoria', 'Defina um limite mensal', 'Registre todas as despesas'];

        switch ($tipo) {
            case 'quase_no_limite':
                $titulo   = "Ei, o orçamento de $categoria tá quase no limite! 🚨";
                $mensagem = "Você já usou boa parte do seu orçamento de $categoria este mês. Melhor segurar um pouco antes de estourar! 😅";
                break;
            case 'limite_atingido':
                $titulo   = "Orçamento de $categoria chegou ao limite 😬";
                $mensagem = "Você atingiu o limite de gastos em $categoria este mês. Hora de apertar o cinto e evitar novas despesas nessa categoria!";
                break;
            case 'limite_ultrapassado':
                $titulo   = "Opa, passou muito do orçamento de $categoria! 😰";
                $mensagem = "Você ultrapassou bastante o limite de $categoria este mês. Bora revisar os gastos com urgência e ver onde dá pra cortar?";
                break;
            case 'orcamento':
                $titulo   = "Orçamento de $categoria ultrapassado ⚠️";
                $mensagem = "Seus gastos em $categoria estão acima do esperado. Confira as dicas abaixo para economizar.";
                break;
            default: // comportamento
                $titulo   = "Gasto elevado em $categoria 📈";
                $mensagem = "Seus gastos em $categoria chamaram atenção este mês. Que tal dar uma olhada e ver onde dá pra economizar?";
                break;
        }

        return [
            'titulo'   => $titulo,
            'mensagem' => $mensagem,
            'acoes'    => $acoes,
        ];
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
        $fonte = json_encode([
            'acoes' => $sugestao['acoes'],
            'tipo'  => $alerta_dados['tipo'],
        ], JSON_UNESCAPED_UNICODE);

        $query = "
            INSERT INTO sugestoes_economia
            (usuario_id, titulo, descricao, fonte, categoria_nome, mes, ano, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo),
            descricao = VALUES(descricao),
            fonte = VALUES(fonte)
        ";

        $stmt = $this->conexao->prepare($query);

        if (!$stmt) {
            error_log("Prepare error: " . $this->conexao->error);
            return null;
        }

        $stmt->bind_param(
            'issssii',
            $usuario_id,
            $titulo,
            $descricao,
            $fonte,
            $categoria,
            $mes,
            $ano
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
        ";

        $stmt = $this->conexao->prepare($query_delete);
        $stmt->bind_param('isii', $usuario_id, $categoria, $mes, $ano);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}
?>
