<?php
// Conexão com MySQL usando MySQLi
$servername = "localhost";
$usuario = "root";
$senha = "";
$banco = "investai";

$conexao = new mysqli($servername, $usuario, $senha, $banco);

// Checar conexão
if ($conexao->connect_error) {
    die("Conexão falhou: " . $conexao->connect_error);
}

$conexao->set_charset("utf8mb4");
?>
