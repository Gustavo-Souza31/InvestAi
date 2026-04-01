<?php
// Conexão com MySQL usando MySQLi
$servername = "localhost";
$usuario = "root";
$senha = "";
$banco = "investai";

// Tenta porta 3306 primeiro, depois 3307 como reserva
$porta = 3306;
$conexao = @new mysqli($servername, $usuario, $senha, $banco, $porta);

if ($conexao->connect_error) {
    $porta = 3307;
    $conexao = new mysqli($servername, $usuario, $senha, $banco, $porta);
}

// Checar conexão final
if ($conexao->connect_error) {
    die("Conexão falhou em ambas as portas (3306 e 3307): " . $conexao->connect_error);
}

$conexao->set_charset("utf8mb4");
?>