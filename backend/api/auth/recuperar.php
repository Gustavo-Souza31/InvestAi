<?php
// backend/api/auth/recuperar.php — Recuperação de senha em duas etapas:
// 1) POST {email}            -> gera token, envia e-mail com link de redefinição
// 2) POST {token, nova_senha} -> valida token e efetiva a nova senha
session_start();
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/validators/ValidatorHelper.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/config/ConfigHelper.php';
require_once $root . '/backend/includes/Mailer.php';

ConfigHelper::load();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$token = trim($_POST['token'] ?? '');

// ── Etapa 2: confirmar nova senha usando o token recebido por e-mail ────────
if ($token !== '') {
    $novaSenha = $_POST['nova_senha'] ?? '';

    if (strlen($novaSenha) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'A nova senha deve ter no mínimo 6 caracteres.']);
        exit;
    }

    $tokenHash = hash('sha256', $token);

    $stmt = $conexao->prepare(
        "SELECT pr.id, pr.usuario_id, pr.expira_em, u.email
         FROM password_resets pr
         JOIN usuarios u ON u.id = pr.usuario_id
         WHERE pr.token_hash = ? AND pr.usado = 0
         ORDER BY pr.id DESC LIMIT 1"
    );
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();

    if (!$reset || strtotime($reset['expira_em']) < time()) {
        Logger::log('WARN', 'PASSWORD_RESET_CONFIRM', ['motivo' => 'Token inválido ou expirado'], 'falha');
        echo json_encode(['status' => 'error', 'message' => 'Link inválido ou expirado. Solicite uma nova recuperação de senha.']);
        exit;
    }

    $usuarioId = (int) $reset['usuario_id'];
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

    $conexao->begin_transaction();
    $upd = $conexao->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
    $upd->bind_param('si', $senhaHash, $usuarioId);
    $upd->execute();

    $mark = $conexao->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = ? AND usado = 0");
    $mark->bind_param('i', $usuarioId);
    $mark->execute();
    $conexao->commit();

    Logger::log('WARN', 'PASSWORD_RESET_CONFIRM', [], 'sucesso', $usuarioId, $reset['email']);
    echo json_encode(['status' => 'success', 'message' => 'Senha alterada com sucesso, você já pode fazer login.']);
    exit;
}

// ── Etapa 1: solicitar recuperação — dispara e-mail com o link ──────────────
$email = trim($_POST['email'] ?? '');

if (!ValidatorHelper::validateEmail($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Informe um e-mail válido.']);
    exit;
}

// Resposta genérica — não revela se o e-mail existe (evita enumeração de contas)
$respostaGenerica = [
    'status'  => 'success',
    'message' => 'Se este e-mail estiver cadastrado, você receberá um link de recuperação em instantes.',
];

$stmt = $conexao->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    Logger::log('INFO', 'PASSWORD_RESET_REQUEST', ['motivo' => 'E-mail não encontrado'], 'falha');
    echo json_encode($respostaGenerica);
    exit;
}

$usuarioId = (int) $usuario['id'];

$tokenPlano = bin2hex(random_bytes(32));
$tokenHash  = hash('sha256', $tokenPlano);
$expiraEm   = date('Y-m-d H:i:s', time() + 1800); // 30 minutos

// Invalida tokens anteriores ainda não usados deste usuário
$invalida = $conexao->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = ? AND usado = 0");
$invalida->bind_param('i', $usuarioId);
$invalida->execute();

$ins = $conexao->prepare("INSERT INTO password_resets (usuario_id, token_hash, expira_em) VALUES (?, ?, ?)");
$ins->bind_param('iss', $usuarioId, $tokenHash, $expiraEm);
$ins->execute();

$appUrl = rtrim((string) ConfigHelper::get('APP_URL', ''), '/');
if ($appUrl === '') {
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $appUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
}
$linkReset = $appUrl . '/frontend/login.php?reset_token=' . $tokenPlano;

$nomeSeguro = htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8');
$bodyHtml = "<p>Olá, {$nomeSeguro}!</p>"
    . "<p>Recebemos uma solicitação para redefinir sua senha no InvestAI.</p>"
    . "<p><a href=\"{$linkReset}\">Clique aqui para criar uma nova senha</a></p>"
    . "<p>Este link expira em 30 minutos. Se você não solicitou isso, ignore este e-mail.</p>";
$bodyText = "Olá, {$usuario['nome']}!\n\nAcesse o link abaixo para redefinir sua senha (válido por 30 minutos):\n{$linkReset}\n\nSe você não solicitou isso, ignore este e-mail.";

$enviado = Mailer::send($email, 'InvestAI — Redefinição de senha', $bodyHtml, $bodyText);

if (!$enviado) {
    Logger::log('ERROR', 'PASSWORD_RESET_REQUEST', ['motivo' => 'Falha ao enviar e-mail (SMTP)'], 'falha', $usuarioId, $email);
    echo json_encode(['status' => 'error', 'message' => 'Não foi possível enviar o e-mail agora. Tente novamente mais tarde ou contate o suporte.']);
    exit;
}

Logger::log('INFO', 'PASSWORD_RESET_REQUEST', [], 'sucesso', $usuarioId, $email);
echo json_encode($respostaGenerica);
