<?php
// Conexão com MySQL usando MySQLi
$servername = "127.0.0.1";
$usuario = "root";
$senha = "";
$banco = "investai";
$porta = 3307;

$conexao = new mysqli($servername, $usuario, $senha, $banco, $porta);

// Checar conexão
if ($conexao->connect_error) {
    die("Conexão falhou: " . $conexao->connect_error);
}

$conexao->set_charset("utf8mb4");
?>