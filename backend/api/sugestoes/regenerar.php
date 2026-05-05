<?php
/**
 * backend/api/sugestoes/regenerar.php
 * 
 * Regenera uma sugestão deletando a antiga e gerando uma nova com IA
 */

header('Content-Type: application/json');

// Debug de requisição
error_log("=== REGENERAR.PHP DEBUG ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NONE'));
error_log("Raw input: " . file_get_contents('php://input'));

require_once dirname(__FILE__) . '/../../includes/auth_middleware.php';

// Verificar autenticação
try {
    $usuario_id = requireAuth();
    error_log("Usuario ID: " . $usuario_id);
} catch (Exception $e) {
    error_log("Auth erro: " . $e->getMessage());
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado']);
    exit;
}

// Carregar .env
$env_file = dirname(__FILE__) . '/../../../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST obrigatório']);
    exit;
}

// Ler e decodificar JSON
$input = file_get_contents('php://input');
error_log("Raw input length: " . strlen($input));

$body = json_decode($input, true);
error_log("Decoded JSON: " . json_encode($body));

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
    exit;
}

$sugestao_id = intval($body['sugestao_id'] ?? 0);
error_log("Sugestao ID: " . $sugestao_id);

if (!$sugestao_id || $sugestao_id < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'sugestao_id inválido: ' . $sugestao_id]);
    exit;
}

try {
    require_once dirname(__FILE__) . '/../../../DataBase/conexao.php';
    error_log("Conexão estabelecida");

    // 1. Buscar dados da sugestão
    $query = "SELECT categoria_nome, mes, ano FROM sugestoes_economia WHERE id = ? AND usuario_id = ?";
    $stmt = $conexao->prepare($query);
    
    if (!$stmt) {
        error_log("Erro ao preparar statement: " . $conexao->error);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro no DB']);
        exit;
    }

    $stmt->bind_param('ii', $sugestao_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    error_log("Query executada, rows: " . $result->num_rows);

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Sugestão não encontrada']);
        exit;
    }

    $sugestao = $result->fetch_assoc();
    error_log("Sugestão encontrada: " . json_encode($sugestao));
    $stmt->close();

    // 2. Deletar sugestão antiga
    $query_delete = "DELETE FROM sugestoes_economia WHERE id = ?";
    $stmt = $conexao->prepare($query_delete);
    $stmt->bind_param('i', $sugestao_id);
    $stmt->execute();
    error_log("Sugestão deletada");
    $stmt->close();

    // 3. Regenerar com IA
    require_once dirname(__FILE__) . '/../../services/EconomySuggestionGenerator.php';
    
    $gemini_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
    error_log("Gemini key presente: " . (strlen($gemini_key) > 0 ? 'SIM' : 'NÃO'));
    
    if (!$gemini_key) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'API key não configurada']);
        exit;
    }

    $generator = new EconomySuggestionGenerator($conexao, $gemini_key);
    error_log("Generator criado");
    
    $novas_sugestoes = $generator->analisarEGerarSugestoes(
        $usuario_id,
        (int)$sugestao['mes'],
        (int)$sugestao['ano']
    );
    error_log("Sugestões geradas: " . count($novas_sugestoes));

    echo json_encode([
        'status' => 'success',
        'message' => 'Sugestão regenerada',
        'sugestoes' => $novas_sugestoes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
    error_log($e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
