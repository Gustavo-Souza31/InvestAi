<?php
// backend/api/despesas/read.php — Retorna lista de despesas do usuário autenticado
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';


// Autenticação
$usuario_id = requireAuth();


// Consultar despesas do usuário
$stmt = $conexao->prepare(
    "SELECT * FROM despesas 
     WHERE usuario_id = ? 
     ORDER BY data_despesa DESC"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$despesas = [];
while ($row = $result->fetch_assoc()) {
    $despesas[] = $row;
}


// Retornar resposta
echo json_encode([
    "status" => "success",
    "despesas" => $despesas
]);
?>
