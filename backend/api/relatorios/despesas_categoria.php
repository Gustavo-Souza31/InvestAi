<?php
// backend/api/relatorios/despesas_categoria.php — Retorna despesas agrupadas por categoria no período
session_start();
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';


$usuario_id = requireAuth();


// Receber parâmetros de query string
$periodo   = $_GET['periodo'] ?? '3m';
$ano       = isset($_GET['ano'])       && $_GET['ano']       !== '' ? intval($_GET['ano'])       : null;
$intervalo = isset($_GET['intervalo']) && $_GET['intervalo'] !== '' ? intval($_GET['intervalo']) : null;

$data_inicio = '';
$data_fim = '';

if ($ano && $intervalo !== null) {
    switch ($periodo) {
        case '1m':
            $data_inicio = sprintf('%04d-%02d-01', $ano, $intervalo);
            $data_fim = date('Y-m-t', strtotime($data_inicio));
            break;
        case '3m':
            $mes_inicio = ($intervalo - 1) * 3 + 1;
            $data_inicio = sprintf('%04d-%02d-01', $ano, $mes_inicio);
            $mes_fim = $intervalo * 3;
            $data_fim = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $ano, $mes_fim)));
            break;
        case '6m':
            $mes_inicio = ($intervalo - 1) * 6 + 1;
            $data_inicio = sprintf('%04d-%02d-01', $ano, $mes_inicio);
            $mes_fim = $intervalo * 6;
            $data_fim = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $ano, $mes_fim)));
            break;
        case '1a':
            $data_inicio = sprintf('%04d-01-01', $ano);
            $data_fim = sprintf('%04d-12-31', $ano);
            break;
    }
} else {
    $data_fim = date('Y-m-d');
    switch ($periodo) {
        case '1m':
            $data_inicio = date('Y-m-d', strtotime('-1 month'));
            break;
        case '3m':
            $data_inicio = date('Y-m-d', strtotime('-3 months'));
            break;
        case '6m':
            $data_inicio = date('Y-m-d', strtotime('-6 months'));
            break;
        case '1a':
            $data_inicio = date('Y-m-d', strtotime('-1 year'));
            break;
        default:
            $data_inicio = date('Y-m-d', strtotime('-3 months'));
    }
}

$sql = "SELECT c.nome AS categoria, COALESCE(SUM(d.valor), 0) AS total
        FROM despesas d
        JOIN categorias c ON d.categoria_id = c.id
        WHERE d.usuario_id = ? AND d.data_despesa BETWEEN ? AND ?
        GROUP BY c.id, c.nome
        ORDER BY total DESC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$valores = [];
$total_geral = 0;

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['categoria'];
    $val = floatval($row['total']);
    $valores[] = $val;
    $total_geral += $val;
}

echo json_encode([
    'status' => 'success',
    'data_inicio' => $data_inicio,
    'data_fim' => $data_fim,
    'labels' => $labels,
    'valores' => $valores,
    'total_geral' => $total_geral
]);
