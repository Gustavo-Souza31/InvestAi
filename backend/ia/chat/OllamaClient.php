<?php
/**
 * backend/ia/chat/OllamaClient.php
 *
 * Wrapper das chamadas HTTP ao Ollama local.
 */

class OllamaClient {

    private string $endpoint;
    private string $model;
    private int $lastHttpCode = 0;

    public function getLastHttpCode(): int { return $this->lastHttpCode; }

    public function __construct() {
        $baseUrl = rtrim(getenv('OLLAMA_URL') ?: 'http://localhost:11434', '/');
        $this->model = getenv('OLLAMA_CHAT_MODEL') ?: getenv('OLLAMA_MODEL') ?: 'llama3.1:latest';
        $this->endpoint = "$baseUrl/api/chat";
    }

    /**
     * Classifica a intenção do usuário e retorna a tool a executar.
     * Usa prompt de classificação compacto com saída JSON — sem native tool calling,
     * que é instável em modelos locais menores com muitas tools.
     * Retorna ['name' => string, 'args' => array] ou null se a chamada falhar.
     */
    public function callWithFunctions(string $message, array $toolDefinitions, array $historico = [], string $nome_usuario = ''): ?array {
        $url = $this->endpoint;

        $messages = [];
        foreach ($historico as $msg) {
            $role  = ($msg['role'] ?? '') === 'usuario' ? 'user' : 'assistant';
            $texto = trim((string) ($msg['texto'] ?? ''));
            if ($texto !== '') {
                $messages[] = ['role' => $role, 'content' => $texto];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $payload = [
            'model'   => $this->model,
            'stream'  => false,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $this->buildClassificationPrompt($nome_usuario)]],
                $messages
            ),
            'options' => [
                'temperature' => 0.0,
                'num_predict' => 256,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        error_log('OllamaClient FC request: msg="' . mb_substr($message, 0, 80) . '"');

        $response = $this->post($url, $body);
        if ($response === null) return null;

        $data = json_decode($response, true);
        $text = trim($data['message']['content'] ?? '');

        $json = $this->extractJson($text);
        if (!$json) {
            error_log('OllamaClient FC sem JSON: ' . mb_substr($text, 0, 200));
            return null;
        }

        $call = json_decode($json, true);
        if (!is_array($call) || empty($call['name'])) {
            error_log('OllamaClient FC JSON inválido: ' . $json);
            return null;
        }

        $args = $call['args'] ?? $call['arguments'] ?? [];
        if (is_string($args)) {
            $args = json_decode($args, true) ?? [];
        }
        if (!is_array($args)) {
            $args = [];
        }

        error_log('OllamaClient FC ok: tool=' . $call['name'] . ' args=' . json_encode($args, JSON_UNESCAPED_UNICODE));
        return ['name' => $call['name'], 'args' => $args];
    }

    /**
     * Geração de texto livre para a resposta amigável.
     * Retorna a string gerada ou null se falhar.
     */
    public function callForText(string $prompt, string $nome_usuario = ''): ?string {
        $url = $this->endpoint;

        $persona = 'Você é o Finn, assistente financeiro do InvestAI. Fale português brasileiro informal e amigável, como um amigo que entende de finanças. Seja direto e use 1 emoji quando fizer sentido.';
        if ($nome_usuario !== '') {
            $persona .= " O nome do usuário é $nome_usuario — use-o naturalmente quando soar bem, não em toda frase.";
        }

        $body = json_encode([
            'model' => $this->model,
            'stream' => false,
            'messages' => [
                ['role' => 'system', 'content' => $persona],
                ['role' => 'user', 'content' => $prompt],
            ],
            'options' => [
                'temperature' => 0.7,
                'num_predict'  => 512,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $response = $this->post($url, $body);
        if ($response === null) return null;

        $data = json_decode($response, true);
        $text = trim($data['message']['content'] ?? '');
        if ($text === '' && isset($data['response'])) {
            $text = trim((string) $data['response']);
        }
        return $text !== '' ? $text : null;
    }

    private function buildClassificationPrompt(string $nome_usuario = ''): string {
        $data_hoje    = date('Y-m-d');
        $ano_atual    = date('Y');
        $proximo_ano  = (string)((int)date('Y') + 1);
        $ultimo_dia_mes = date('Y-m-t');
        $nome_ctx     = $nome_usuario !== '' ? "User name: {$nome_usuario}." : '';

        return <<<PROMPT
You are a JSON function dispatcher for a personal finance app. {$nome_ctx}
You MUST respond with ONLY a valid JSON object. No explanation, no markdown, no extra text.

FUNCTIONS — choose exactly one:
criar_despesa      → user spent/paid/bought something (gastei, paguei, comprei, comi, fui ao)
criar_ganho        → user received/earned money (recebi, ganhei, salário, freela, bico, trampo)
editar_despesa     → correct a recently logged expense (errei, na verdade, foi, corrige a despesa)
editar_ganho       → correct a recently logged income
deletar_despesa    → delete a specific expense
deletar_ganho      → delete a specific income entry
deletar_todas_despesas  → delete ALL expenses (todas as despesas / todos os gastos)
deletar_todos_ganhos    → delete ALL income entries (todos os ganhos)
consultar_gastos   → show/list expenses (ver, listar, quanto gastei, meus gastos)
consultar_ganhos   → show/list income (ver, listar, quanto recebi, meus ganhos)
resumo_dashboard   → financial summary or overview (resumo, visão geral, como estou financeiramente)
criar_orcamento    → create budget for a category (criar orçamento de X para Y)
editar_orcamento   → change a budget limit
deletar_orcamento  → delete a specific budget
deletar_todos_orcamentos → delete ALL budgets
criar_meta         → create a savings goal (meta de, objetivo de, quero juntar/guardar)
editar_meta        → edit an existing savings goal
deletar_meta       → delete a specific goal
deletar_todas_metas → delete ALL goals
consultar_metas    → show goals (minhas metas, quanto já guardei, progresso)
criar_aporte       → add money to a goal (depositei, coloquei, aportei, guardei para a meta)
editar_aporte      → correct a contribution to a goal
deletar_aporte     → delete a contribution
consultar_aportes  → show contribution history for goals
pedir_confirmacao  → required info is missing and cannot be inferred; ask user with field "pergunta"
conversa           → greeting, thanks, question about capabilities, or unrelated chat
acao_indisponivel  → requested feature does not exist in the system

EXPENSE CATEGORIES (infer from keywords in the message):
Alimentação        → pizza, almoço, jantar, lanche, comida, mercado, restaurante, ifood, delivery, sushi, hambúrguer, padaria, marmita, açaí, café
Saúde              → remédio, médico, academia, farmácia, consulta, dentista, psicólogo, hospital, plano de saúde
Transporte         → uber, gasolina, ônibus, metrô, taxi, combustível, passagem, bilhete único, pedágio
Entretenimento     → netflix, cinema, spotify, show, jogo, steam, balada, ingresso, disney+, amazon prime
Utilidades Domésticas → luz, água, internet, energia, gás, wi-fi, conta de luz, conta de água
Habitação          → aluguel, condomínio, reforma, IPTU, encanador, eletricista
Educação           → curso, faculdade, escola, livro, udemy, aula particular, certificação
Vestuário e Acessórios → roupa, tênis, calçado, sapato, bolsa, mochila, camisa, calça, vestido

INCOME CATEGORIES: Salário, Freelance, Investimentos, Outros
(salário/CLT=Salário, freela/bico/trampo/serviço=Freelance, dividendo/rendimento/CDB=Investimentos)

RULES:
- "3 mil" = 3000, "50 conto" = 50, "uma grana" = unknown value
- "pode X?" / "consegue X?" / "tem como X?" = request to DO X, not a question about capability
- If value AND category are clear → use the action tool directly, do NOT use pedir_confirmacao
- If value is missing and cannot be inferred → use pedir_confirmacao
- For criar_orcamento: NEVER ask for sub-categories or breakdowns. If the user gives a category name and a value, create the budget immediately. Categories are fixed (Alimentação, Entretenimento, etc.) — sub-items like "filme", "jogo", "música" do NOT exist as separate budgets.
- For editar_despesa/editar_ganho on the most recent entry: use descricao_busca=""
- For deletar_despesa/deletar_meta/etc. on first call: always use confirmado=false
- Check conversation history for context continuity before classifying
- Today: {$data_hoje}
- descricao for criar_despesa/criar_ganho: extract the SPECIFIC activity/context from the message, NOT just the category name. Keep it short (2-5 words), Title Case, no numbers.
  Examples: "fiz um freelancer no shopping" → "Freelancer no Shopping"
            "ganhei 100 de pintura de paredes" → "Pintura de Paredes"
            "gastei 50 num almoço com a família" → "Almoço com Família"
            "paguei a conta de luz" → "Conta de Luz"
            "recebi meu salário" → "Salário Mensal"
- prazo for criar_meta: infer from implicit time expressions; omit ONLY if no time reference at all.
  "até o fim do ano" / "no final do ano" / "até dezembro" → {$ano_atual}-12-31
  "até o fim do mês" → {$ultimo_dia_mes}
  "próximo ano" / "ano que vem" → {$proximo_ano}-12-31
  "em X meses" → calculate from today ({$data_hoje}) + X months

OUTPUT FORMAT — respond with ONLY this JSON, nothing else:
{"name":"FUNCTION_NAME","args":{...}}

EXAMPLES:
{"name":"criar_despesa","args":{"valor":50,"categoria":"Alimentação","descricao":"Pizza no Restaurante","data":"{$data_hoje}"}}
{"name":"criar_ganho","args":{"valor":100,"categoria":"Freelance","descricao":"Freelancer no Shopping"}}
{"name":"criar_ganho","args":{"valor":3000,"categoria":"Salário","descricao":"Salário Mensal"}}
{"name":"editar_despesa","args":{"descricao_busca":"","novo_valor":25}}
{"name":"pedir_confirmacao","args":{"pergunta":"Qual foi o valor da despesa? 💸"}}
{"name":"conversa","args":{}}
{"name":"criar_orcamento","args":{"categoria":"Alimentação","valor":500}}
{"name":"criar_orcamento","args":{"categoria":"Entretenimento","valor":300}}
{"name":"criar_meta","args":{"nome":"Comprar Carro","valor_total":30000,"prazo":"{$ano_atual}-12-31"}}
{"name":"criar_meta","args":{"nome":"Viagem para Europa","valor_total":10000}}
{"name":"criar_aporte","args":{"meta_nome_busca":"moto","valor":200}}
PROMPT;
    }

    private function extractJson(string $text): ?string {
        $text = trim($text);
        if ($text === '') return null;

        if (preg_match('/```(?:json)?\s*([\s\S]+?)```/i', $text, $matches)) {
            return trim($matches[1]);
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            return trim(substr($text, $start, $end - $start + 1));
        }

        return null;
    }

    private function post(string $url, string $body): ?string {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => (int)(getenv('OLLAMA_TIMEOUT') ?: 120),
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->lastHttpCode = $http_code;

        if (!$response || $http_code !== 200) {
            error_log("OllamaClient HTTP $http_code - " . substr((string) $response, 0, 500));
            return null;
        }

        error_log("OllamaClient HTTP $http_code ok (" . strlen($response) . " bytes)");

        return $response;
    }
}
