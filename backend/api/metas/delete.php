<?php
// backend/api/metas/delete.php — Deleta (ou desativa) meta
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';

$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados do body JSON
$body    = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$meta_id = isset($body['id']) ? intval($body['id']) : null;

if (!$meta_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID da meta é obrigatório.']);
    exit;
}

// Marcar como inativa para preservar histórico
$stmt = $conexao->prepare("UPDATE metas SET ativo = 0, atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $meta_id, $usuario_id);

if ($stmt->execute()) {
    Logger::log('INFO', 'META_DELETED', ['id' => $meta_id], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Meta removida com sucesso!']);
} else {
    Logger::log('ERROR', 'META_DELETED', ['id' => $meta_id], 'falha', $usuario_id, $usuario_email);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao remover meta.']);
}
?>
