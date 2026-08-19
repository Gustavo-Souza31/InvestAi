<?php
// backend/api/sugestoes/economia.php — Gera e retorna sugestões de economia do mês
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/ia/sugestoes_economia/EconomySuggestionGenerator.php';


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}


// Receber parâmetros de query string
$mes = (int) ($_GET['mes'] ?? date('m'));
$ano = (int) ($_GET['ano'] ?? date('Y'));


// Validar mês e ano
if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mês ou ano inválido.']);
    exit;
}


// Carregar chave Gemini do .env
$env_file = $root . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
        $_ENV[trim($k)] = trim($v);
    }
}

$gemini_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');


// Gerar sugestões de economia
try {
    $generator = new EconomySuggestionGenerator($conexao, $gemini_key);
    $sugestoes = $generator->analisarEGerarSugestoes($usuario_id, $mes, $ano);

    Logger::log('INFO', 'AI_SUGGESTION_GENERATED', ['mes' => $mes, 'ano' => $ano, 'total' => count($sugestoes)], 'sucesso', $usuario_id, $usuario_email);

    echo json_encode([
        'status'    => 'success',
        'sugestoes' => $sugestoes,
        'total'     => count($sugestoes),
        'mes'       => $mes,
        'ano'       => $ano,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    Logger::log('ERROR', 'AI_SUGGESTION_FAILED', ['erro' => $e->getMessage(), 'mes' => $mes, 'ano' => $ano], 'falha', $usuario_id, $usuario_email);
    error_log("Erro em economia.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao gerar sugestões: ' . $e->getMessage()]);
}
?>
