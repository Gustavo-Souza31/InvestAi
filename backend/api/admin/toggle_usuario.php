<?php
// backend/api/admin/toggle_usuario.php — Ativa ou desativa conta de usuário
session_start();
header('Content-Type: application/json; charset=utf-8');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/admin_middleware.php';
require_once $root . '/backend/includes/Logger.php';

requireAdmin();

$admin_id    = $_SESSION['usuario_id'];
$admin_email = $_SESSION['usuario_email'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$body        = json_decode(file_get_contents('php://input'), true);
$usuario_id  = intval($body['usuario_id'] ?? 0);
$novo_estado = isset($body['ativo']) ? (int) (bool) $body['ativo'] : null;

if ($usuario_id <= 0 || $novo_estado === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'usuario_id e ativo são obrigatórios.']);
    exit;
}

// Impedir que o admin desative a própria conta
if ($usuario_id === $admin_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Não é possível alterar a própria conta pelo painel admin.']);
    exit;
}

// Buscar dados do usuário alvo para o log
$stmt_u = $conexao->prepare("SELECT email FROM usuarios WHERE id = ?");
$stmt_u->bind_param('i', $usuario_id);
$stmt_u->execute();
$row_u = $stmt_u->get_result()->fetch_assoc();

if (!$row_u) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Usuário não encontrado.']);
    exit;
}

$alvo_email = $row_u['email'];

// Atualizar estado
$stmt = $conexao->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
$stmt->bind_param('ii', $novo_estado, $usuario_id);

$acao  = $novo_estado ? 'USER_ACTIVATED' : 'USER_DEACTIVATED';
$nivel = $novo_estado ? 'INFO' : 'WARN';

if ($stmt->execute()) {
    $msg = $novo_estado ? 'Conta ativada.' : 'Conta desativada.';
    Logger::log($nivel, $acao, ['alvo_id' => $usuario_id, 'alvo_email' => $alvo_email], 'sucesso', $admin_id, $admin_email);
    echo json_encode(['status' => 'success', 'message' => $msg, 'ativo' => (bool) $novo_estado]);
} else {
    Logger::log('ERROR', $acao, ['alvo_id' => $usuario_id], 'falha', $admin_id, $admin_email);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar usuário.']);
}
?>
