<?php
require_once __DIR__ . '/../../../DataBase/conexao.php';
header('Content-Type: application/json');

$usuario_id = $_GET['usuario_id'] ?? 1;

$stmt = $conexao->prepare("SELECT * FROM ganhos WHERE usuario_id = ? ORDER BY data_ganho DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$ganhos = [];
while ($row = $result->fetch_assoc()) {
    $ganhos[] = $row;
}

echo json_encode(["status" => "success", "ganhos" => $ganhos]);
?>
