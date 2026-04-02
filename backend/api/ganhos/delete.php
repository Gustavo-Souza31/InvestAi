<?php
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/IdValidator.php';


requireAuth();


// Receber ID
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


// Verificar se existe
$stmt = $conexao->prepare(
    'SELECT id FROM ganhos WHERE id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ganho não encontrado.'
    ]);
    exit;
}


// Deletar
$stmt = $conexao->prepare(
    'DELETE FROM ganhos WHERE id = ?'
);
$stmt->bind_param('i', $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Ganho excluído!'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao excluir ganho.'
    ]);
}
?>
