<?php
session_start();
header('Content-Type: application/json');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/validators/AuthValidator.php';

// Receber dados de FormData
$data = [
    'nome' => $_POST['nome'] ?? '',
    'email' => $_POST['email'] ?? '',
    'cpf' => $_POST['cpf'] ?? '',
    'telefone' => $_POST['telefone'] ?? '',
    'senha' => $_POST['senha'] ?? ''
];

// Validar
$validation = AuthValidator::validateCadastro($data);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$nome = $validation['data']['nome'];
$email = $validation['data']['email'];
$cpf = $validation['data']['cpf'];
$telefone = $validation['data']['telefone'];
$senha = $validation['data']['senha'];

// Verifica duplicatas
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ? OR cpf = ? OR telefone = ?");
$stmt->bind_param('sss', $email, $cpf, $telefone);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'E-mail, CPF ou telefone já cadastrado.']);
    exit;
}

// Inserir usuário
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, cpf, telefone, senha_hash) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('sssss', $nome, $email, $cpf, $telefone, $senha_hash);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao criar conta.']);
    exit;
}

$usuario_id = $conexao->insert_id;

// Iniciar sessão
$_SESSION['usuario_id']   = $usuario_id;
$_SESSION['usuario_nome'] = $nome;

echo json_encode([
    'status'   => 'success',
    'message'  => 'Conta criada com sucesso!',
    'nome'     => $nome,
    'redirect' => 'dashboard.php'
]);
?>
