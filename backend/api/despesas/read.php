<?php
require_once __DIR__ . '/../../../DataBase/conexao.php';
header('Content-Type: application/json');

$usuario_id = $_GET['usuario_id'] ?? 1;

$stmt = $conexao->prepare("SELECT * FROM despesas WHERE usuario_id = ? ORDER BY data_despesa DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$despesas = [];
while ($row = $result->fetch_assoc()) {
    $despesas[] = $row;
}

echo json_encode(["status" => "success", "despesas" => $despesas]);
?>
