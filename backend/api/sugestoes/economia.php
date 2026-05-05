<?php
/**
 * backend/api/sugestoes/economia.php
 * 
 * Endpoint para gerar e recuperar sugestões de economia
 * 
 * GET /api/sugestoes/economia.php?mes=5&ano=2026
 * Retorna array de sugestões para o usuário no mês/ano especificado
 * 
 * Response: { status, sugestoes: [{ categoria, tipo, titulo, mensagem, acoes, gasto, limite }] }
 */

require_once dirname(__FILE__) . '/../../includes/auth_middleware.php';
require_once dirname(__FILE__) . '/../../services/EconomySuggestionGenerator.php';

// Carregar .env
$env_file = dirname(__FILE__) . '/../../../.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
        $_ENV[trim($k)] = trim($v);
    }
}

// Verificar autenticação
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensagem' => 'Método não permitido']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensagem' => 'Não autenticado']);
    exit;
}

// Validar parâmetros
$mes = (int) ($_GET['mes'] ?? date('m'));
$ano = (int) ($_GET['ano'] ?? date('Y'));

if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensagem' => 'Mês ou ano inválido']);
    exit;
}

try {
    // Conectar banco
    require_once dirname(__FILE__) . '/../../../DataBase/conexao.php';

    // Carregar Gemini Key
    $gemini_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');

    // Criar gerador de sugestões
    $generator = new EconomySuggestionGenerator($conexao, $gemini_key);

    // Analisar e gerar sugestões
    $sugestoes = $generator->analisarEGerarSugestoes($usuario_id, $mes, $ano);

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'sugestoes' => $sugestoes,
        'total' => count($sugestoes),
        'mes' => $mes,
        'ano' => $ano,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Erro em economia.php: " . $e->getMessage() . " | " . $e->getFile() . ":" . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'mensagem' => 'Erro ao gerar sugestões: ' . $e->getMessage(),
    ]);
}
?>
