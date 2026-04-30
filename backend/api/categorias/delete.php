<?php
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';

$usuario_id = requireAuth();
$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID não informado.']);
    exit;
}

$stmt = $conexao->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $id, $usuario_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Categoria excluída com sucesso!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Categoria não encontrada ou você não tem permissão para excluí-la.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir categoria.']);
}
?>
