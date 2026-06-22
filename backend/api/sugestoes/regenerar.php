<?php
// backend/api/sugestoes/regenerar.php — Regenera uma sugestão deletando a antiga e chamando a IA novamente
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/ia/sugestoes_economia/EconomySuggestionGenerator.php';


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST obrigatório.']);
    exit;
}


// Receber dados do body JSON
$body        = json_decode(file_get_contents('php://input'), true);
$sugestao_id = intval($body['sugestao_id'] ?? 0);


// Validar ID
if ($sugestao_id < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'sugestao_id inválido.']);
    exit;
}


// Buscar dados da sugestão
$stmt = $conexao->prepare("SELECT categoria_nome, mes, ano FROM sugestoes_economia WHERE id = ? AND usuario_id = ?");
$stmt->bind_param('ii', $sugestao_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Sugestão não encontrada.']);
    exit;
}

$sugestao = $result->fetch_assoc();
$stmt->close();


// Deletar sugestão antiga para forçar regeneração
$stmt = $conexao->prepare("DELETE FROM sugestoes_economia WHERE id = ?");
$stmt->bind_param('i', $sugestao_id);
$stmt->execute();
$stmt->close();


// Regenerar sugestão com IA
try {
    $generator       = new EconomySuggestionGenerator($conexao);
    $novas_sugestoes = $generator->analisarEGerarSugestoes(
        $usuario_id,
        (int) $sugestao['mes'],
        (int) $sugestao['ano']
    );

    Logger::log('INFO', 'AI_SUGGESTION_REGENERATED', ['sugestao_id' => $sugestao_id, 'categoria' => $sugestao['categoria_nome']], 'sucesso', $usuario_id, $usuario_email);

    echo json_encode([
        'status'    => 'success',
        'message'   => 'Sugestão regenerada.',
        'sugestoes' => $novas_sugestoes,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    Logger::log('ERROR', 'AI_SUGGESTION_FAILED', ['erro' => $e->getMessage(), 'sugestao_id' => $sugestao_id], 'falha', $usuario_id, $usuario_email);
    error_log("Erro em regenerar.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
