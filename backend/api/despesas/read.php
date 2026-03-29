<?php
// backend/api/despesas/read.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/auth_middleware.php';
$usuario_id = requireAuth();
require_once __DIR__ . '/../../includes/db.php';

$mes = isset($_GET['mes']) ? intval($_GET['mes']) : null;
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : null;

if ($mes && $ano) {
    $stmt = $conexao->prepare("SELECT * FROM despesas WHERE usuario_id = ? AND MONTH(data_despesa) = ? AND YEAR(data_despesa) = ? ORDER BY data_despesa DESC");
    $stmt->bind_param("iii", $usuario_id, $mes, $ano);
} else {
    $stmt = $conexao->prepare("SELECT * FROM despesas WHERE usuario_id = ? ORDER BY data_despesa DESC");
    $stmt->bind_param("i", $usuario_id);
}

$stmt->execute();
$result = $stmt->get_result();
$despesas = [];
while ($row = $result->fetch_assoc()) {
    $despesas[] = $row;
}

echo json_encode([
    "status" => "success",
    "despesas" => $despesas,
    "total" => count($despesas)
]);

$stmt->close();
$conexao->close();
?>
