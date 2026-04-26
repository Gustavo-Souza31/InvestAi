<?php
/**
 * backend/api/noticias/explain.php
 * Recebe uma notícia e retorna uma explicação didática via Gemini AI.
 */


ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Não autorizado."]);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$noticia = $body['noticia'] ?? null;

if (!$noticia || empty($noticia['titulo'])) {
    http_response_code(400);
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Dados da notícia inválidos."]);
    exit;
}

// ─── Chave Gemini ──────────────────────────────────────────────────────────
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
    echo json_encode(["status" => "sem_chave", "mensagem" => "Chave Gemini API não configurada."]);
    exit;
}

// ─── Perfil do usuário (para personalizar a explicação) ──────────────────
$root       = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$inicio_mes = date('Y-m-01');
$hoje       = date('Y-m-d');

$stmt = $conexao->prepare("SELECT saldo_inicial, renda_mensal, objetivo_financeiro FROM perfil_financeiro WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc();

$stmt = $conexao->prepare("SELECT COALESCE(SUM(valor),0) as total FROM ganhos WHERE usuario_id=? AND data_ganho BETWEEN ? AND ?");
$stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
$stmt->execute();
$ganhos = floatval($stmt->get_result()->fetch_assoc()['total']);

$stmt = $conexao->prepare("SELECT COALESCE(SUM(valor),0) as total FROM despesas WHERE usuario_id=? AND data_despesa BETWEEN ? AND ?");
$stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
$stmt->execute();
$despesas = floatval($stmt->get_result()->fetch_assoc()['total']);

$saldo_atual = floatval($perfil['saldo_inicial'] ?? 0) + $ganhos - $despesas;
$renda       = floatval($perfil['renda_mensal'] ?? 0);
$objetivo    = $perfil['objetivo_financeiro'] ?? 'Não informado';

// ─── Montar Prompt ─────────────────────────────────────────────────────────
$titulo  = $noticia['titulo']  ?? '';
$resumo  = $noticia['resumo']  ?? '';
$fonte   = $noticia['fonte']   ?? '';
$data    = $noticia['data']    ?? '';
$saldo_f = number_format($saldo_atual, 2, ',', '.');
$renda_f = number_format($renda, 2, ',', '.');

$prompt = <<<PROMPT
Você é o Arquiteto Financeiro do InvestAI, especialista em traduzir notícias econômicas complexas para linguagem simples e prática.

PERFIL DO USUÁRIO:
- Saldo atual: R$ {$saldo_f}
- Renda mensal: R$ {$renda_f}
- Objetivo financeiro: {$objetivo}

NOTÍCIA:
- Título: {$titulo}
- Fonte: {$fonte}
- Data: {$data}
- Resumo: {$resumo}

TAREFA:
Explique esta notícia de forma DIDÁTICA e PERSONALIZADA para este usuário. Responda APENAS com JSON puro (sem markdown, sem ```), seguindo exatamente esta estrutura:

{
  "manchete": "Reescreva o título de forma ainda mais clara e acessível (máx 100 chars)",
  "o_que_aconteceu": "Explique em 2-3 frases simples O QUE aconteceu, como se fosse para alguém que não entende de economia",
  "por_que_importa": "Explique em 2-3 frases POR QUE isso é importante para o brasileiro comum",
  "impacto_no_bolso": "1-2 frases diretas sobre como isso pode afetar o bolso especificamente deste usuário com base no perfil dele",
  "o_que_fazer": [
    "Ação concreta 1 que o usuário pode tomar agora",
    "Ação concreta 2 (se houver)",
    "Ação concreta 3 (se houver)"
  ],
  "palavras_chave": [
    {"termo": "Termo técnico da notícia", "definicao": "Definição simples em 1 frase"},
    {"termo": "Outro termo", "definicao": "Definição simples"}
  ],
  "nivel_impacto": "alto|medio|baixo",
  "resumo_tweet": "Resuma a notícia e o impacto em 1 frase curta (como um tweet)"
}

Seja acessível, direto e útil. Use linguagem de fácil compreensão. Máximo 3 palavras-chave. Máximo 3 ações. Responda em português do Brasil.
PROMPT;

// ─── Chamar Gemini API ─────────────────────────────────────────────────────
$api_url  = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . urlencode($gemini_key);
$req_body = json_encode([
    "contents" => [["parts" => [["text" => $prompt]]]],
    "generationConfig" => ["temperature" => 0.65, "maxOutputTokens" => 1500]
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $req_body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response  = curl_exec($ch);
$curl_err  = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_err || !$response) {
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Erro de conexão com a Gemini API: " . $curl_err]);
    exit;
}

$gemini_data = json_decode($response, true);
if ($http_code !== 200) {
    $err_msg = $gemini_data['error']['message'] ?? "Erro HTTP {$http_code}";
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Gemini API: " . $err_msg]);
    exit;
}

$raw = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '';
$raw = trim($raw);
if (preg_match('/```(?:json)?\s*([\s\S]+?)```/', $raw, $m)) {
    $raw = trim($m[1]);
}

$resultado = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Erro ao parsear resposta da IA.", "raw" => mb_substr($raw, 0, 300)]);
    exit;
}

$resultado['status'] = 'ok';
ob_clean();
echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
