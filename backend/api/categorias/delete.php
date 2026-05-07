<?php
// backend/api/categorias/delete.php — Deleta categoria do usuário após validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/validators/CategoriasValidator.php';


// Autenticação
$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados do body JSON
$input      = json_decode(file_get_contents('php://input'), true) ?: [];
$validation = CategoriasValidator::validateDelete($input);


// Validar ID
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$id = $validation['data']['id'];


// Deletar categoria do banco de dados
$stmt = $conexao->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $id, $usuario_id);


// Executar e verificar deleção
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        Logger::log('INFO', 'CATEGORIA_DELETED', ['id' => $id], 'sucesso', $usuario_id, $usuario_email);
        echo json_encode(['status' => 'success', 'message' => 'Categoria excluída com sucesso!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Categoria não encontrada ou você não tem permissão para excluí-la.']);
    }
} else {
    Logger::log('ERROR', 'CATEGORIA_DELETED', ['id' => $id], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir categoria.']);
}
?>
