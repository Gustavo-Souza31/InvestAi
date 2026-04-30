<?php
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';

$usuario_id = requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;
$novo_nome = isset($data['nome']) ? trim($data['nome']) : '';

if ($id <= 0 || empty($novo_nome)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos para edição.']);
    exit;
}

// Verifica se a categoria pertence ao usuário
$check_stmt = $conexao->prepare("SELECT id FROM categorias WHERE id = ? AND usuario_id = ?");
$check_stmt->bind_param("ii", $id, $usuario_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Categoria não encontrada ou não autorizada.']);
    exit;
}

// Atualiza o nome da categoria
$stmt = $conexao->prepare("UPDATE categorias SET nome = ? WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("sii", $novo_nome, $id, $usuario_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Categoria atualizada com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar categoria.']);
}
?>
