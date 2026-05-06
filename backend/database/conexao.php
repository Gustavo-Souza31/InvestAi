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

$conexao = new mysqli($servername, $usuario_db, $senha_db, $banco, $porta);

if ($conexao->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Falha na conexão com o banco de dados."]);
    exit;
}

$conexao->set_charset("utf8mb4");
?>
