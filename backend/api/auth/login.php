<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$senha = $data['senha'] ?? '';

if (empty($email) || empty($senha)) {
    echo json_encode(['status' => 'error', 'message' => 'Preencha e-mail e senha.']);
    exit;
}

$stmt = $conexao->prepare("SELECT id, nome, senha_hash FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'E-mail ou senha incorretos.']);
    exit;
}

$usuario = $result->fetch_assoc();

if (!password_verify($senha, $usuario['senha_hash'])) {
    echo json_encode(['status' => 'error', 'message' => 'E-mail ou senha incorretos.']);
    exit;
}

$_SESSION['usuario_id']   = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];

echo json_encode([
    'status'  => 'success',
    'message' => 'Login realizado com sucesso!',
    'nome'    => $usuario['nome'],
    'redirect' => 'dashboard.php'
]);
