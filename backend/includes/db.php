<?php
// backend/includes/db.php — Conexão centralizada ao banco
$servername = "127.0.0.1";
$usuario_db = "root";
$senha_db = "";
$banco = "investai";
$porta = 3307;

$conexao = new mysqli($servername, $usuario_db, $senha_db, $banco, $porta);

if ($conexao->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Falha na conexão com o banco de dados."]);
    exit;
}

$conexao->set_charset("utf8mb4");
?>