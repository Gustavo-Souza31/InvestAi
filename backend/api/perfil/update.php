<?php
// backend/api/perfil/update.php — Atualiza dados do perfil do usuário
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';

$usuario_id = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

// Receber dados
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$renda_mensal = floatval($_POST['renda_mensal'] ?? 0);
$objetivo_financeiro = trim($_POST['objetivo_financeiro'] ?? '');
$perfil_comportamento = trim($_POST['perfil_comportamento'] ?? 'moderado');
$senha_atual = $_POST['senha_atual'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';

// Validações básicas
$errors = [];

if (empty($nome) || strlen($nome) < 3) {
    $errors[] = 'Nome deve ter pelo menos 3 caracteres.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail inválido.';
}

if (empty($telefone)) {
    $errors[] = 'Telefone é obrigatório.';
}

if (!in_array($perfil_comportamento, ['conservador', 'moderado', 'gastador'])) {
    $perfil_comportamento = 'moderado';
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => $errors[0]]);
    exit;
}

// Verificar se email já existe para outro user
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
$stmt->bind_param('si', $email, $usuario_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Este e-mail já está em uso por outra conta.']);
    exit;
}

// Verificar se telefone já existe para outro user
$telefone_limpo = preg_replace('/\D/', '', $telefone);
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE telefone = ? AND id != ?");
$stmt->bind_param('si', $telefone_limpo, $usuario_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Este telefone já está em uso por outra conta.']);
    exit;
}

// Se deseja alterar senha
if (!empty($nova_senha)) {
    if (strlen($nova_senha) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Nova senha deve ter pelo menos 6 caracteres.']);
        exit;
    }

    // Verificar senha atual
    $stmt = $conexao->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!password_verify($senha_atual, $row['senha_hash'])) {
        echo json_encode(['status' => 'error', 'message' => 'Senha atual incorreta.']);
        exit;
    }

    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $stmt = $conexao->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, senha_hash = ? WHERE id = ?");
    $stmt->bind_param('ssssi', $nome, $email, $telefone_limpo, $senha_hash, $usuario_id);
} else {
    $stmt = $conexao->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ?");
    $stmt->bind_param('sssi', $nome, $email, $telefone_limpo, $usuario_id);
}

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar dados pessoais.']);
    exit;
}

// Atualizar perfil financeiro (upsert)
$stmtCheck = $conexao->prepare("SELECT id FROM perfil_financeiro WHERE usuario_id = ?");
$stmtCheck->bind_param('i', $usuario_id);
$stmtCheck->execute();

if ($stmtCheck->get_result()->num_rows > 0) {
    $stmtPerfil = $conexao->prepare(
        "UPDATE perfil_financeiro SET renda_mensal = ?, objetivo_financeiro = ?, perfil_comportamento = ? WHERE usuario_id = ?"
    );
    $stmtPerfil->bind_param('dssi', $renda_mensal, $objetivo_financeiro, $perfil_comportamento, $usuario_id);
} else {
    $stmtPerfil = $conexao->prepare(
        "INSERT INTO perfil_financeiro (usuario_id, renda_mensal, objetivo_financeiro, perfil_comportamento) VALUES (?, ?, ?, ?)"
    );
    $stmtPerfil->bind_param('idss', $usuario_id, $renda_mensal, $objetivo_financeiro, $perfil_comportamento);
}

if (!$stmtPerfil->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar perfil financeiro.']);
    exit;
}

// Atualizar session
$_SESSION['usuario_nome'] = $nome;

echo json_encode([
    'status' => 'success',
    'message' => 'Perfil atualizado com sucesso!',
    'nome' => $nome
]);
?>
