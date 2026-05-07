<?php
session_start();

$root = dirname(__DIR__);
require_once $root . '/backend/includes/Logger.php';

// Logar antes de destruir a sessão (precisamos dos dados ainda)
$usuario_id    = $_SESSION['usuario_id']    ?? null;
$usuario_email = $_SESSION['usuario_email'] ?? null;

Logger::log('INFO', 'USER_LOGOUT', [], 'sucesso', $usuario_id, $usuario_email);

session_destroy();
header('Location: index.php');
exit;
?>
