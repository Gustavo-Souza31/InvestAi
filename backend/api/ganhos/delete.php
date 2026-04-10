<?php
// backend/api/ganhos/delete.php — Deleta ganho após validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/IdValidator.php';


requireAuth();


// Receber ID do ganho
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


// Verificar se ganho existe
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


// Deletar ganho do banco de dados
$stmt = $conexao->prepare(
    'DELETE FROM ganhos WHERE id = ?'
);
$stmt->bind_param('i', $id);

// Executar e verificar delecao
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
