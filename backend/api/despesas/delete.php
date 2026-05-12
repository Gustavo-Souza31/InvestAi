<?php
// backend/api/despesas/delete.php — Deleta despesa após validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/validators/IdValidator.php';


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber ID da despesa
$id = intval($_POST['id'] ?? 0);


// Validar ID
$validation = IdValidator::validateId($id);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}


// Verificar se despesa existe e pertence ao usuário
$stmt = $conexao->prepare('SELECT id FROM despesas WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $id, $usuario_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Despesa não encontrada.']);
    exit;
}


// Deletar despesa do banco de dados
$stmt = $conexao->prepare('DELETE FROM despesas WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $id, $usuario_id);


// Executar e verificar deleção
if ($stmt->execute() && $stmt->affected_rows > 0) {
    Logger::log('INFO', 'DESPESA_DELETED', ['id' => $id], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Despesa excluída!']);
} else {
    Logger::log('ERROR', 'DESPESA_DELETED', ['id' => $id], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir despesa.']);
}
?>
