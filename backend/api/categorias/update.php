<?php
// backend/api/categorias/update.php — Atualiza o nome de uma categoria do usuário
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
$validation = CategoriasValidator::validateUpdate($input);


// Validar dados contra regras de negócio
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$id   = $validation['data']['id'];
$nome = $validation['data']['nome'];


// Verificar se a categoria pertence ao usuário
$check_stmt = $conexao->prepare("SELECT id FROM categorias WHERE id = ? AND usuario_id = ?");
$check_stmt->bind_param("ii", $id, $usuario_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Categoria não encontrada ou não autorizada.']);
    exit;
}


// Atualizar categoria no banco de dados
$stmt = $conexao->prepare("UPDATE categorias SET nome = ? WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("sii", $nome, $id, $usuario_id);


// Executar e verificar atualização
if ($stmt->execute()) {
    Logger::log('INFO', 'CATEGORIA_UPDATED', ['id' => $id, 'nome' => $nome], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Categoria atualizada com sucesso.']);
} else {
    Logger::log('ERROR', 'CATEGORIA_UPDATED', ['id' => $id], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar categoria.']);
}
?>
