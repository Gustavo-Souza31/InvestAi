<?php
// backend/api/despesas/delete.php — Deleta despesa após validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/IdValidator.php';


// Autenticação
requireAuth();


// Receber ID da despesa
$id = intval($_POST['id'] ?? 0);


// Validar ID
$validation = IdValidator::validateId($id);
if (!$validation['valid']) {
    echo json_encode([
        'status' => 'error',
        'message' => $validation['errors'][0]
    ]);
    exit;
}


// Verificar se despesa existe
$stmt = $conexao->prepare(
    'SELECT id FROM despesas WHERE id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Despesa não encontrada.'
    ]);
    exit;
}


// Deletar despesa do banco de dados
$stmt = $conexao->prepare(
    'DELETE FROM despesas WHERE id = ?'
);
$stmt->bind_param('i', $id);


// Executar e verificar delecao
if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Despesa excluída!'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao excluir despesa.'
    ]);
}
?>
