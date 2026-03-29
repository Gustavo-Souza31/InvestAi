<?php
// backend/includes/db.php — Conexão centralizada ao banco
$servername = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "investai";

$conexao = new mysqli($servername, $usuario_db, $senha_db, $banco);

if ($conexao->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Falha na conexão com o banco de dados."]);
    exit;
}

$conexao->set_charset("utf8mb4");
?>
