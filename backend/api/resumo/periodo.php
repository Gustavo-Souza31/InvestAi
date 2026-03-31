<?php
require_once __DIR__ . '/../../../DataBase/conexao.php';
header('Content-Type: application/json');

$usuario_id = $_GET['usuario_id'] ?? 1;
$periodo = $_GET['periodo'] ?? 'mensal';

// Calcular data de início com base no período
switch ($periodo) {
    case 'semanal':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'mensal':
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'trimestral':
        $data_inicio = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'semestral':
        $data_inicio = date('Y-m-d', strtotime('-180 days'));
        break;
    case 'anual':
        $data_inicio = date('Y-m-d', strtotime('-365 days'));
        break;
    default:
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        break;
}

$data_fim = date('Y-m-d');

// Total de ganhos no período
$stmt = $conexao->prepare("SELECT COALESCE(SUM(valor), 0) AS total FROM ganhos WHERE usuario_id = ? AND data_ganho BETWEEN ? AND ?");
$stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
$stmt->execute();
$total_ganhos = $stmt->get_result()->fetch_assoc()['total'];

// Total de despesas no período
$stmt = $conexao->prepare("SELECT COALESCE(SUM(valor), 0) AS total FROM despesas WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?");
$stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
$stmt->execute();
$total_despesas = $stmt->get_result()->fetch_assoc()['total'];

$saldo = $total_ganhos - $total_despesas;

echo json_encode([
    "status" => "success",
    "periodo" => $periodo,
    "data_inicio" => $data_inicio,
    "data_fim" => $data_fim,
    "total_ganhos" => (float) $total_ganhos,
    "total_despesas" => (float) $total_despesas,
    "saldo" => (float) $saldo
]);
?>
