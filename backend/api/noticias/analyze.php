<?php
/**
 * backend/api/noticias/analyze.php
 * Analisa notícias via Gemini com categorização obrigatória em 7 tags,
 * 3 ações práticas por notícia e cruzamento com despesas do usuário no banco.
 */

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');

// ─── Autenticação ───────────────────────────────────────────────────────────
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Não autorizado."]);
    exit;
}

// ─── Corpo da requisição ────────────────────────────────────────────────────
$body     = json_decode(file_get_contents('php://input'), true);
$noticias = $body['noticias'] ?? [];

if (empty($noticias)) {
    http_response_code(400);
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Nenhuma notícia recebida para análise."]);
    exit;
}

// ─── Chave Gemini ───────────────────────────────────────────────────────────
function get_gemini_key(): ?string {
    $key = getenv('GEMINI_API_KEY');
    if ($key) return trim($key);

    $env_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/.env';
    if (file_exists($env_path)) {
        foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (strpos($line, 'GEMINI_API_KEY=') === 0) {
                return trim(substr($line, strlen('GEMINI_API_KEY=')), " \"'");
            }
        }
    }
    return null;
}

$gemini_key = get_gemini_key();
if (!$gemini_key) {
    ob_clean();
    echo json_encode([
        "status"            => "sem_chave",
        "mensagem"          => "Chave Gemini API não configurada.",
        "resumo_geral"      => "Configure a chave da API Gemini para ativar a análise de IA personalizada.",
        "nivel_alerta"      => "baixo",
        "analises"          => [],
        "top_acao_da_semana" => "Configure sua chave Gemini API para receber recomendações personalizadas.",
        "categorias_usuario" => []
    ]);
    exit;
}

// ─── Perfil financeiro e despesas do banco ──────────────────────────────────
$root    = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$hoje       = date('Y-m-d');
$inicio_mes = date('Y-m-01');

// Perfil
$stmt = $conexao->prepare(
    "SELECT saldo_inicial, renda_mensal, objetivo_financeiro, perfil_comportamento
     FROM perfil_financeiro WHERE usuario_id = ?"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc();

// Ganhos do mês
$stmt = $conexao->prepare(
    "SELECT COALESCE(SUM(valor), 0) as total FROM ganhos WHERE usuario_id = ? AND data_ganho BETWEEN ? AND ?"
);
$stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
$stmt->execute();
$total_ganhos = floatval($stmt->get_result()->fetch_assoc()['total']);

// Despesas do mês
$stmt = $conexao->prepare(
    "SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?"
);
$stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
$stmt->execute();
$total_despesas = floatval($stmt->get_result()->fetch_assoc()['total']);

// Descrições de despesas (para cruzamento com categorias da IA)
$stmt = $conexao->prepare(
    "SELECT descricao FROM despesas WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ? ORDER BY valor DESC LIMIT 20"
);
$stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
$stmt->execute();
$res = $stmt->get_result();
$descricoes_despesas = [];
while ($row = $res->fetch_assoc()) {
    $descricoes_despesas[] = mb_strtolower($row['descricao']);
}

$saldo_atual = floatval($perfil['saldo_inicial'] ?? 0) + $total_ganhos - $total_despesas;

// ─── Mapeamento de palavras-chave → categoria ────────────────────────────────
/**
 * Mapeia os textos livres das descrições de despesas do usuário para
 * as 7 categorias fixas do sistema.
 */
$mapa_categorias = [
    'Transporte'      => ['combustível','gasolina','diesel','etanol','ônibus','metro','metrô','uber','99','táxi','táxi','passagem','trem','bicicleta','estacionamento','pedágio','frete','moto','gás veículo','carro'],
    'Alimentação'     => ['aliment','comida','supermercado','mercado','feira','restaurante','lanchonete','padaria','açougue','hortifruti','cesta básica','delivery','ifood','rappi','pizza','hambúrguer','café','refeição','jantar','almoço','café da manhã'],
    'Moradia'         => ['aluguel','condomínio','iptu','água','luz','energia elétrica','gás','internet residencial','reforma','construção','imóvel','apartamento','casa','financiamento imóvel','aluguel'],
    'Lazer'           => ['cinema','netflix','spotify','disney','entretenimento','viagem','hotel','passagem aérea','turismo','esporte','academia','jogo','show','evento','teatro','bar','festa','lazer','streaming'],
    'Tecnologia'      => ['celular','smartphone','computador','notebook','tablet','software','aplicativo','assinatura digital','internet','plano de dados','tecnologia','eletrônico','gadget','impressora'],
    'Saúde'           => ['saúde','plano de saúde','consulta','médico','dentista','remédio','medicamento','farmácia','hospital','clínica','exame','laboratório','fisioterapia','academia saúde','vacina','psicólogo'],
    'Finanças Gerais' => ['investimento','poupança','tesouro','fundo','ação','bolsa','seguro','previdência','imposto','ir','conta','banco','empréstimo','financiamento','cartão','crédito','débito','taxa','tarifa'],
];

/**
 * Retorna o conjunto de categorias que o usuário TEM despesas cadastradas,
 * cruzando as descrições com o mapa de palavras-chave.
 */
function mapear_categorias_usuario(array $descricoes, array $mapa): array {
    $encontradas = [];
    foreach ($descricoes as $desc) {
        foreach ($mapa as $categoria => $palavras) {
            if (in_array($categoria, $encontradas)) continue;
            foreach ($palavras as $p) {
                if (mb_strpos($desc, $p) !== false) {
                    $encontradas[] = $categoria;
                    break;
                }
            }
        }
    }
    return array_values(array_unique($encontradas));
}

$categorias_usuario = mapear_categorias_usuario($descricoes_despesas, $mapa_categorias);

// ─── Montar Prompt ──────────────────────────────────────────────────────────
$noticias_texto = "";
foreach (array_slice($noticias, 0, 10) as $i => $n) {
    $idx    = $i + 1;
    $fonte  = $n['fonte']  ?? '';
    $titulo = $n['titulo'] ?? '';
    $resumo = mb_substr($n['resumo'] ?? '', 0, 200);
    $noticias_texto .= "\n{$idx}. [{$fonte}] {$titulo}\n   Resumo: {$resumo}\n";
}

$categorias_usuario_str = !empty($categorias_usuario)
    ? implode(", ", $categorias_usuario)
    : "não identificadas";

$objetivo  = $perfil['objetivo_financeiro']  ?? 'Não informado';
$perfil_c  = $perfil['perfil_comportamento'] ?? 'moderado';
$renda     = number_format(floatval($perfil['renda_mensal'] ?? 0), 2, ',', '.');
$saldo_fmt = number_format($saldo_atual, 2, ',', '.');
$ganhos_f  = number_format($total_ganhos, 2, ',', '.');
$desp_f    = number_format($total_despesas, 2, ',', '.');

$prompt = <<<PROMPT
Você é o Arquiteto Financeiro do InvestAI. Analise as notícias econômicas abaixo e gere um relatório de impacto PERSONALIZADO.

PERFIL DO USUÁRIO:
- Saldo atual: R$ {$saldo_fmt}
- Renda mensal: R$ {$renda}
- Ganhos registrados no mês: R$ {$ganhos_f}
- Despesas registradas no mês: R$ {$desp_f}
- Objetivo financeiro: {$objetivo}
- Perfil de comportamento: {$perfil_c}
- Categorias de gasto do usuário: {$categorias_usuario_str}

NOTÍCIAS DO DIA:
{$noticias_texto}

CATEGORIAS FIXAS PERMITIDAS (use EXATAMENTE um destes valores em "categoria"):
["Transporte", "Alimentação", "Moradia", "Lazer", "Tecnologia", "Saúde", "Finanças Gerais"]

TAREFA:
Analise cada notícia e responda APENAS com JSON puro (sem markdown, sem ```, sem comentários), seguindo exatamente esta estrutura:

{
  "resumo_geral": "2-3 frases contextualizando o cenário econômico atual e o impacto no perfil do usuário",
  "nivel_alerta": "baixo|medio|alto",
  "analises": [
    {
      "titulo_noticia": "título exato da notícia",
      "categoria": "UMA das 7 categorias fixas acima — obrigatório",
      "impacto": "alto|medio|baixo",
      "cenario_hipotetico": "Descreva em 1-2 frases um cenário hipotético de como esta notícia pode afetar as finanças do usuário nos próximos 3-6 meses",
      "como_afeta": "1 frase direta explicando como afeta o orçamento/investimentos deste usuário",
      "acoes_praticas": [
        "Ação prática 1 que o usuário pode tomar agora",
        "Ação prática 2",
        "Ação prática 3"
      ],
      "sugestao_investimento": "1 ação concreta de investimento",
      "dica_economia": "1 dica prática para economizar ou proteger o orçamento"
    }
  ],
  "top_acao_da_semana": "A única coisa mais importante que o usuário deve fazer esta semana com base nas notícias"
}

REGRAS OBRIGATÓRIAS:
1. O campo "categoria" DEVE ser exatamente um dos 7 valores fixos listados. Não invente categorias.
2. O campo "acoes_praticas" DEVE ter EXATAMENTE 3 itens.
3. Foque nas notícias mais relevantes para o perfil financeiro do usuário.
4. Responda em português do Brasil.
5. Seja direto, pragmático e personalizado.
PROMPT;

// ─── Chamar Gemini API ──────────────────────────────────────────────────────
$api_url  = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . urlencode($gemini_key);
$req_body = json_encode([
    "contents" => [["parts" => [["text" => $prompt]]]],
    "generationConfig" => [
        "temperature"     => 0.65,
        "maxOutputTokens" => 2500,
    ]
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $req_body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 35,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response  = curl_exec($ch);
$curl_err  = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_err || !$response) {
    ob_clean();
    echo json_encode([
        "status"   => "error",
        "mensagem" => "Erro de conexão com a Gemini API: " . ($curl_err ?: "sem resposta")
    ]);
    exit;
}

$gemini_data = json_decode($response, true);

if ($http_code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
    $err_msg = $gemini_data['error']['message'] ?? "Erro HTTP {$http_code}";
    ob_clean();
    echo json_encode([
        "status"   => "error",
        "mensagem" => "Gemini API retornou erro: " . $err_msg
    ]);
    exit;
}

// ─── Extrair e limpar JSON da resposta ──────────────────────────────────────
$raw = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '';
$raw = trim($raw);
if (preg_match('/```(?:json)?\s*([\s\S]+?)```/', $raw, $m)) {
    $raw = trim($m[1]);
}

$resultado = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_clean();
    echo json_encode([
        "status"   => "error",
        "mensagem" => "Erro ao parsear resposta da IA.",
        "raw"      => mb_substr($raw, 0, 500)
    ]);
    exit;
}

// ─── Cruzamento: marcar notícias relevantes para o usuário ──────────────────
if (!empty($resultado['analises'])) {
    foreach ($resultado['analises'] as &$analise) {
        $cat = $analise['categoria'] ?? '';
        $analise['relevante_para_usuario'] = in_array($cat, $categorias_usuario);
    }
    unset($analise);
}

// ─── Retornar resultado ──────────────────────────────────────────────────────
$resultado['status']            = 'ok';
$resultado['categorias_usuario'] = $categorias_usuario;

ob_clean();
echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
