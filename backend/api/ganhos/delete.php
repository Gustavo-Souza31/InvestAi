<?php
// backend/api/ganhos/delete.php — Deleta ganho após validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/validators/IdValidator.php';


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber ID do ganho
$id = intval($_POST['id'] ?? 0);


// Validar ID
$validation = IdValidator::validateId($id);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}


// Verificar se ganho existe
$stmt = $conexao->prepare('SELECT id FROM ganhos WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Ganho não encontrado.']);
    exit;
}


// Deletar ganho do banco de dados
$stmt = $conexao->prepare('DELETE FROM ganhos WHERE id = ?');
$stmt->bind_param('i', $id);


// Executar e verificar deleção
if ($stmt->execute() && $stmt->affected_rows > 0) {
    Logger::log('INFO', 'GANHO_DELETED', ['id' => $id], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Ganho excluído!']);
} else {
    Logger::log('ERROR', 'GANHO_DELETED', ['id' => $id], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir ganho.']);
}
?>
