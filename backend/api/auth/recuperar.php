<?php
// backend/api/auth/recuperar.php — Redefine a senha do usuário via e-mail
session_start();
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/validators/AuthValidator.php';


// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}


// Receber dados de FormData
$data = [
    'email'      => $_POST['email']      ?? '',
    'nova_senha' => $_POST['nova_senha'] ?? '',
];


// Validar dados contra regras de negócio
$validation = AuthValidator::validateRecuperacao($data);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$email      = $validation['data']['email'];
$nova_senha = $validation['data']['nova_senha'];


// Verificar se o e-mail existe
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'E-mail não encontrado no sistema.']);
    exit;
}


// Atualizar senha no banco de dados
$senha_hash   = password_hash($nova_senha, PASSWORD_DEFAULT);
$stmt_update  = $conexao->prepare("UPDATE usuarios SET senha_hash = ? WHERE email = ?");
$stmt_update->bind_param('ss', $senha_hash, $email);


// Executar e verificar atualização
if ($stmt_update->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Senha alterada com sucesso, você já pode fazer login.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar a senha. Tente novamente mais tarde.']);
}
?>
