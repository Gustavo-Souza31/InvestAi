<?php
// backend/api/auth/login.php — Autentica usuário via email e senha
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


// Receber dados de FormData
$data = [
    'email' => $_POST['email'] ?? '',
    'senha' => $_POST['senha'] ?? ''
];


// Validar dados contra regras de negócio
$validation = AuthValidator::validateLogin($data);
if (!$validation['valid']) {
    echo json_encode([
        'status' => 'error',
        'message' => $validation['errors'][0]
    ]);
    exit;
}

$email = $validation['data']['email'];
$senha = $validation['data']['senha'];


// Buscar usuário no banco de dados
$stmt = $conexao->prepare(
    "SELECT id, nome, senha_hash FROM usuarios WHERE email = ?"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'E-mail ou senha incorretos.'
    ]);
    exit;
}

$usuario = $result->fetch_assoc();


// Verificar se a senha está correta usando hashing seguro
if (!password_verify($senha, $usuario['senha_hash'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'E-mail ou senha incorretos.'
    ]);
    exit;
}


// Iniciar sessão com dados do usuário
$_SESSION['usuario_id']   = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];


// Retornar sucesso
echo json_encode([
    'status'   => 'success',
    'message'  => 'Login realizado com sucesso!',
    'nome'     => $usuario['nome'],
    'redirect' => 'dashboard.php'
]);
?>
