<?php
// backend/api/auth/cadastro.php — Cria nova conta com validação de campos e duplicatas
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
    'nome' => $_POST['nome'] ?? '',
    'email' => $_POST['email'] ?? '',
    'cpf' => $_POST['cpf'] ?? '',
    'telefone' => $_POST['telefone'] ?? '',
    'senha' => $_POST['senha'] ?? ''
];


// Validar
$validation = AuthValidator::validateCadastro($data);
if (!$validation['valid']) {
    echo json_encode([
        'status' => 'error',
        'message' => $validation['errors'][0]
    ]);
    exit;
}

$nome = $validation['data']['nome'];
$email = $validation['data']['email'];
$cpf = $validation['data']['cpf'];
$telefone = $validation['data']['telefone'];
$senha = $validation['data']['senha'];


// Verificar email duplicado
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'E-mail já cadastrado. Tente outro ou faça login.'
    ]);
    exit;
}


// Verificar CPF duplicado
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE cpf = ?");
$stmt->bind_param('s', $cpf);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'CPF já cadastrado. Tente outro ou faça login.'
    ]);
    exit;
}


// Verificar telefone duplicado
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE telefone = ?");
$stmt->bind_param('s', $telefone);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Telefone já cadastrado. Tente outro ou faça login.'
    ]);
    exit;
}


// Inserir usuário
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conexao->prepare(
    "INSERT INTO usuarios (nome, email, cpf, telefone, senha_hash) 
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssss', $nome, $email, $cpf, $telefone, $senha_hash);


// Executar e verificar inserção
if (!$stmt->execute()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao criar conta.'
    ]);
    exit;
}

$usuario_id = $conexao->insert_id;


// Iniciar sessão
$_SESSION['usuario_id']   = $usuario_id;
$_SESSION['usuario_nome'] = $nome;
$_SESSION['is_first_login'] = true; // Flag for Product Tour


// Retornar sucesso
echo json_encode([
    'status'   => 'success',
    'message'  => 'Conta criada com sucesso!',
    'nome'     => $nome,
    'redirect' => 'dashboard.php'
]);
?>
