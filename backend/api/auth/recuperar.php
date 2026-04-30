<?php
session_start();
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/validators/AuthValidator.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método não permitido.'
    ]);
    exit;
}

// Receber dados
$data = [
    'email' => $_POST['email'] ?? '',
    'nova_senha' => $_POST['nova_senha'] ?? ''
];

// Validar
$validation = AuthValidator::validateRecuperacao($data);
if (!$validation['valid']) {
    echo json_encode([
        'status' => 'error',
        'message' => $validation['errors'][0]
    ]);
    exit;
}

$email = $validation['data']['email'];
$nova_senha = $validation['data']['nova_senha'];

// Verificar se o email existe
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'E-mail não encontrado no sistema.'
    ]);
    exit;
}

// Atualizar a senha
$senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
$stmt_update = $conexao->prepare("UPDATE usuarios SET senha_hash = ? WHERE email = ?");
$stmt_update->bind_param('ss', $senha_hash, $email);

if ($stmt_update->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Senha alterada com sucesso, você já pode fazer login'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao atualizar a senha. Tente novamente mais tarde.'
    ]);
}
?>