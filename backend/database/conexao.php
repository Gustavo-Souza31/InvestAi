<?php
// backend/database/conexao.php — Conexão centralizada ao banco
// Carrega configurações de .env

require_once dirname(dirname(__DIR__)) . '/backend/config/ConfigHelper.php';
ConfigHelper::load();

$servername = ConfigHelper::get('DB_HOST', '127.0.0.1');
$usuario_db = ConfigHelper::get('DB_USER', 'root');
$senha_db = ConfigHelper::get('DB_PASS', '');
$banco = ConfigHelper::get('DB_NAME', 'investai');
$porta = ConfigHelper::get('DB_PORT', 3306);

$conexao = mysqli_init();
// TiDB Serverless e outros bancos em nuvem exigem conexão segura (SSL)
$conexao->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
$conexao->ssl_set(NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);

// Tenta conectar, permitindo SSL caso o banco exija
$conexao->real_connect($servername, $usuario_db, $senha_db, $banco, $porta, NULL, MYSQLI_CLIENT_SSL);

if ($conexao->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Falha na conexão com o banco de dados."]);
    exit;
}

$conexao->set_charset("utf8mb4");
?>
