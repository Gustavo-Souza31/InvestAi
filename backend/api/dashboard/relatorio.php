<?php
/**
 * relatorio.php — API de relatório financeiro agrupado por período
 * 
 * Parâmetros GET:
 *   periodo: 1s (1 semana), 3m (3 meses), 6m (6 meses), 1a (1 ano)
 * 
 * Retorna JSON com labels, ganhos[] e despesas[] para Chart.js
 */

session_start();
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Não autenticado.'
    ]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$periodo = $_GET['periodo'] ?? '3m';

// Definir período e tipo de agrupamento baseado no parâmetro
$hoje = date('Y-m-d');

switch ($periodo) {
    case '1m':
        $data_inicio = date('Y-m-d', strtotime('-1 month'));
        $agrupamento = 'dia';
        break;
    case '3m':
        $data_inicio = date('Y-m-d', strtotime('-3 months'));
        $agrupamento = 'mes';
        break;
    case '6m':
        $data_inicio = date('Y-m-d', strtotime('-6 months'));
        $agrupamento = 'mes';
        break;
    case '1a':
        $data_inicio = date('Y-m-d', strtotime('-1 year'));
        $agrupamento = 'mes';
        break;
    default:
        $data_inicio = date('Y-m-d', strtotime('-3 months'));
        $agrupamento = 'mes';
}


// Consultar ganhos agrupados por período
if ($agrupamento === 'dia') {
    $sql_ganhos = "SELECT DATE(data_ganho) AS periodo, COALESCE(SUM(valor), 0) AS total
                   FROM ganhos
                   WHERE usuario_id = ? AND data_ganho BETWEEN ? AND ?
                   GROUP BY DATE(data_ganho)
                   ORDER BY periodo ASC";
} else {
    $sql_ganhos = "SELECT DATE_FORMAT(data_ganho, '%Y-%m') AS periodo, COALESCE(SUM(valor), 0) AS total
                   FROM ganhos
                   WHERE usuario_id = ? AND data_ganho BETWEEN ? AND ?
                   GROUP BY DATE_FORMAT(data_ganho, '%Y-%m')
                   ORDER BY periodo ASC";
}

$stmt = $conexao->prepare($sql_ganhos);
$stmt->bind_param("iss", $usuario_id, $data_inicio, $hoje);
$stmt->execute();
$result_ganhos = $stmt->get_result();

// Armazenar ganhos em mapa associativo para lookup rápido
$ganhos_map = [];
while ($row = $result_ganhos->fetch_assoc()) {
    $ganhos_map[$row['periodo']] = floatval($row['total']);
}


// ===== DESPESAS agrupadas =====
if ($agrupamento === 'dia') {
    $sql_despesas = "SELECT DATE(data_despesa) AS periodo, COALESCE(SUM(valor), 0) AS total
                     FROM despesas
                     WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?
                     GROUP BY DATE(data_despesa)
                     ORDER BY periodo ASC";
} else {
    $sql_despesas = "SELECT DATE_FORMAT(data_despesa, '%Y-%m') AS periodo, COALESCE(SUM(valor), 0) AS total
                     FROM despesas
                     WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?
                     GROUP BY DATE_FORMAT(data_despesa, '%Y-%m')
                     ORDER BY periodo ASC";
}

$stmt = $conexao->prepare($sql_despesas);
$stmt->bind_param("iss", $usuario_id, $data_inicio, $hoje);
$stmt->execute();
$result_despesas = $stmt->get_result();

$despesas_map = [];
while ($row = $result_despesas->fetch_assoc()) {
    $despesas_map[$row['periodo']] = floatval($row['total']);
}


// ===== Gerar labels completos (preencher períodos sem dados com 0) =====
$labels = [];
$ganhos_data = [];
$despesas_data = [];

$meses_pt = [
    '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
    '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
    '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
];

if ($agrupamento === 'dia') {
    // Gerar cada dia do período
    $current = new DateTime($data_inicio);
    $end = new DateTime($hoje);
    $end->modify('+1 day');
    
    while ($current < $end) {
        $key = $current->format('Y-m-d');
        $label = $current->format('d/m');
        
        $labels[] = $label;
        $ganhos_data[] = $ganhos_map[$key] ?? 0;
        $despesas_data[] = $despesas_map[$key] ?? 0;
        
        $current->modify('+1 day');
    }
} else {
    // Gerar cada mês do período
    $current = new DateTime($data_inicio);
    $current->modify('first day of this month');
    $end = new DateTime($hoje);
    $end->modify('first day of next month');
    
    while ($current < $end) {
        $key = $current->format('Y-m');
        $mes_num = $current->format('m');
        $ano_short = $current->format('y');
        $label = $meses_pt[$mes_num] . '/' . $ano_short;
        
        $labels[] = $label;
        $ganhos_data[] = $ganhos_map[$key] ?? 0;
        $despesas_data[] = $despesas_map[$key] ?? 0;
        
        $current->modify('+1 month');
    }
}


// ===== Totais para gráfico de rosca =====
$total_ganhos = array_sum($ganhos_data);
$total_despesas = array_sum($despesas_data);


echo json_encode([
    'status' => 'success',
    'periodo' => $periodo,
    'labels' => $labels,
    'ganhos' => $ganhos_data,
    'despesas' => $despesas_data,
    'total_ganhos' => $total_ganhos,
    'total_despesas' => $total_despesas
]);
?>
