<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../../DataBase/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
$senha    = $_POST['senha'] ?? '';

// Validações básicas
if (empty($nome) || empty($email) || empty($cpf) || empty($telefone) || empty($senha)) {
    echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'E-mail inválido.']);
    exit;
}

if (strlen($cpf) !== 11) {
    echo json_encode(['status' => 'error', 'message' => 'CPF inválido. Use apenas os 11 dígitos.']);
    exit;
}

if (strlen($senha) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'A senha deve ter ao menos 6 caracteres.']);
    exit;
}

// Verifica duplicatas
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ? OR cpf = ? OR telefone = ?");
$stmt->bind_param('sss', $email, $cpf, $telefone);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'E-mail, CPF ou telefone já cadastrado.']);
    exit;
}

// Insere o usuário
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, cpf, telefone, senha_hash) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('sssss', $nome, $email, $cpf, $telefone, $senha_hash);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao criar conta. Tente novamente.']);
    exit;
}

$usuario_id = $conexao->insert_id;

// Inicia sessão
$_SESSION['usuario_id']   = $usuario_id;
$_SESSION['usuario_nome'] = $nome;

echo json_encode([
    'status'   => 'success',
    'message'  => 'Conta criada com sucesso!',
    'nome'     => $nome,
    'redirect' => '/dashboard.php'
]);
