<?php
// backend/api/auth/login.php — Autentica usuário via email e senha
session_start();
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/validators/AuthValidator.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/config/ConfigHelper.php';


// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
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
    Logger::log('WARN', 'USER_LOGIN_FAILED', ['motivo' => 'Dados inválidos'], 'falha');
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$email = $validation['data']['email'];
$senha = $validation['data']['senha'];


// ── Verificar credenciais de admin (.env) ────────────────────────────────────
// O admin não precisa de conta no banco — credenciais vivem apenas no .env
ConfigHelper::load();
$admin_email    = ConfigHelper::get('ADMIN_EMAIL', '');
$admin_password = ConfigHelper::get('ADMIN_PASSWORD', '');

if (
    $admin_email !== '' &&
    $admin_password !== '' &&
    $email === $admin_email &&
    $senha === $admin_password
) {
    $_SESSION['usuario_id']    = 0;
    $_SESSION['usuario_nome']  = 'Admin';
    $_SESSION['usuario_email'] = $admin_email;
    $_SESSION['is_admin']      = true;

    Logger::log('INFO', 'USER_LOGIN', ['tipo' => 'admin'], 'sucesso');

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Login realizado com sucesso!',
        'nome'     => 'Admin',
        'redirect' => 'public/pages/admin/admin.php',
    ]);
    exit;
}


// ── Autenticação de usuário normal (banco de dados) ──────────────────────────
try {
    $stmt = $conexao->prepare(
        "SELECT id, nome, email, senha_hash, ativo FROM usuarios WHERE email = ?"
    );
    if (!$stmt) throw new Exception("Tabela usuarios não encontrada. Você importou o schema.sql?");
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro no Banco: ' . $e->getMessage()]);
    exit;
}

if ($result->num_rows === 0) {
    Logger::log('WARN', 'USER_LOGIN_FAILED', ['motivo' => 'E-mail não encontrado'], 'falha');
    echo json_encode(['status' => 'error', 'message' => 'E-mail ou senha incorretos.']);
    exit;
}

$usuario = $result->fetch_assoc();


// Verificar se a conta está ativa
if (!(int) $usuario['ativo']) {
    Logger::log('WARN', 'USER_LOGIN_BLOCKED', [], 'falha', (int) $usuario['id'], $usuario['email']);
    echo json_encode(['status' => 'error', 'message' => 'Conta desativada. Entre em contato com o suporte.']);
    exit;
}


// Verificar senha
if (!password_verify($senha, $usuario['senha_hash'])) {
    Logger::log('WARN', 'USER_LOGIN_FAILED', ['motivo' => 'Senha incorreta'], 'falha', (int) $usuario['id'], $usuario['email']);
    echo json_encode(['status' => 'error', 'message' => 'E-mail ou senha incorretos.']);
    exit;
}


// Iniciar sessão
$_SESSION['usuario_id']    = $usuario['id'];
$_SESSION['usuario_nome']  = $usuario['nome'];
$_SESSION['usuario_email'] = $usuario['email'];
$_SESSION['is_admin']      = false;

Logger::log('INFO', 'USER_LOGIN', [], 'sucesso', (int) $usuario['id'], $usuario['email']);


// Retornar sucesso
echo json_encode([
    'status'   => 'success',
    'message'  => 'Login realizado com sucesso!',
    'nome'     => $usuario['nome'],
    'redirect' => 'public/pages/user/dashboard.php',
]);
?>
