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
require_once $root . '/backend/database/conexao.php';

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
$categoria_id = isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '' ? intval($_GET['categoria_id']) : null;
$ano = isset($_GET['ano']) && $_GET['ano'] !== '' ? intval($_GET['ano']) : null;
$intervalo = isset($_GET['intervalo']) && $_GET['intervalo'] !== '' ? intval($_GET['intervalo']) : null;
$tipo_comparacao = $_GET['comparacao'] ?? 'yoy'; // yoy ou consecutivo

// Definir período e tipo de agrupamento
$data_inicio = '';
$data_fim = '';
$data_inicio_ant = '';
$data_fim_ant = '';
$agrupamento = 'mes';

if ($ano && $intervalo !== null) {
    // Usar datas exatas baseadas na seleção do usuário
    switch ($periodo) {
        case '1m':
            $data_inicio = sprintf('%04d-%02d-01', $ano, $intervalo);
            $data_fim = date('Y-m-t', strtotime($data_inicio));
            
            if ($tipo_comparacao === 'yoy') {
                $data_inicio_ant = date('Y-m-01', strtotime($data_inicio . ' -1 year'));
                $data_fim_ant = date('Y-m-t', strtotime($data_fim . ' -1 year'));
            } else {
                $data_inicio_ant = date('Y-m-01', strtotime($data_inicio . ' -1 month'));
                $data_fim_ant = date('Y-m-t', strtotime($data_inicio_ant));
            }
            
            $agrupamento = 'dia';
            break;
        case '3m':
            $mes_inicio = ($intervalo - 1) * 3 + 1;
            $data_inicio = sprintf('%04d-%02d-01', $ano, $mes_inicio);
            $mes_fim = $intervalo * 3;
            $data_fim = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $ano, $mes_fim)));
            
            if ($tipo_comparacao === 'yoy') {
                $data_inicio_ant = date('Y-m-01', strtotime($data_inicio . ' -1 year'));
                $data_fim_ant = date('Y-m-t', strtotime($data_fim . ' -1 year'));
            } else {
                $data_inicio_ant = date('Y-m-01', strtotime($data_inicio . ' -3 months'));
                $data_fim_ant = date('Y-m-t', strtotime($data_inicio_ant . ' +2 months'));
            }
            
            $agrupamento = 'mes';
            break;
        case '6m':
            $mes_inicio = ($intervalo - 1) * 6 + 1;
            $data_inicio = sprintf('%04d-%02d-01', $ano, $mes_inicio);
            $mes_fim = $intervalo * 6;
            $data_fim = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $ano, $mes_fim)));
            
            if ($tipo_comparacao === 'yoy') {
                $data_inicio_ant = date('Y-m-01', strtotime($data_inicio . ' -1 year'));
                $data_fim_ant = date('Y-m-t', strtotime($data_fim . ' -1 year'));
            } else {
                $data_inicio_ant = date('Y-m-01', strtotime($data_inicio . ' -6 months'));
                $data_fim_ant = date('Y-m-t', strtotime($data_inicio_ant . ' +5 months'));
            }
            
            $agrupamento = 'mes';
            break;
        case '1a':
            $data_inicio = sprintf('%04d-01-01', $ano);
            $data_fim = sprintf('%04d-12-31', $ano);
            
            $data_inicio_ant = sprintf('%04d-01-01', $ano - 1);
            $data_fim_ant = sprintf('%04d-12-31', $ano - 1);
            
            $agrupamento = 'mes';
            break;
    }
} else {
    // Fallback para datas relativas (comportamento original)
    $data_fim = date('Y-m-d');
    switch ($periodo) {
        case '1m':
            $data_inicio = date('Y-m-d', strtotime('-1 month'));
            if ($tipo_comparacao === 'yoy') {
                $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -1 year'));
                $data_fim_ant = date('Y-m-d', strtotime('-1 day -1 year'));
            } else {
                $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -1 month'));
                $data_fim_ant = date('Y-m-d', strtotime($data_inicio . ' -1 day'));
            }
            $agrupamento = 'dia';
            break;
        case '3m':
            $data_inicio = date('Y-m-d', strtotime('-3 months'));
            if ($tipo_comparacao === 'yoy') {
                $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -1 year'));
                $data_fim_ant = date('Y-m-d', strtotime('-1 day -1 year'));
            } else {
                $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -3 months'));
                $data_fim_ant = date('Y-m-d', strtotime($data_inicio . ' -1 day'));
            }
            $agrupamento = 'mes';
            break;
        case '6m':
            $data_inicio = date('Y-m-d', strtotime('-6 months'));
            if ($tipo_comparacao === 'yoy') {
                $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -1 year'));
                $data_fim_ant = date('Y-m-d', strtotime('-1 day -1 year'));
            } else {
                $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -6 months'));
                $data_fim_ant = date('Y-m-d', strtotime($data_inicio . ' -1 day'));
            }
            $agrupamento = 'mes';
            break;
        case '1a':
            $data_inicio = date('Y-m-d', strtotime('-1 year'));
            // 1 ano YoY ou Consecutivo é sempre 1 ano antes
            $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -1 year'));
            $data_fim_ant = date('Y-m-d', strtotime($data_inicio . ' -1 day'));
            $agrupamento = 'mes';
            break;
        default:
            $data_inicio = date('Y-m-d', strtotime('-3 months'));
            if ($tipo_comparacao === 'yoy') {
                $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -1 year'));
                $data_fim_ant = date('Y-m-d', strtotime('-1 day -1 year'));
            } else {
                $data_inicio_ant = date('Y-m-d', strtotime($data_inicio . ' -3 months'));
                $data_fim_ant = date('Y-m-d', strtotime($data_inicio . ' -1 day'));
            }
            $agrupamento = 'mes';
    }
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
$stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
$stmt->execute();
$result_ganhos = $stmt->get_result();

// Armazenar ganhos em mapa associativo para lookup rápido
$ganhos_map = [];
while ($row = $result_ganhos->fetch_assoc()) {
    $ganhos_map[$row['periodo']] = floatval($row['total']);
}


// ===== DESPESAS agrupadas =====
$filtro_categoria = $categoria_id ? " AND categoria_id = ?" : "";

if ($agrupamento === 'dia') {
    $sql_despesas = "SELECT DATE(data_despesa) AS periodo, COALESCE(SUM(valor), 0) AS total
                     FROM despesas
                     WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?$filtro_categoria
                     GROUP BY DATE(data_despesa)
                     ORDER BY periodo ASC";
} else {
    $sql_despesas = "SELECT DATE_FORMAT(data_despesa, '%Y-%m') AS periodo, COALESCE(SUM(valor), 0) AS total
                     FROM despesas
                     WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?$filtro_categoria
                     GROUP BY DATE_FORMAT(data_despesa, '%Y-%m')
                     ORDER BY periodo ASC";
}

$stmt = $conexao->prepare($sql_despesas);
if ($categoria_id) {
    $stmt->bind_param("issi", $usuario_id, $data_inicio, $data_fim, $categoria_id);
} else {
    $stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
}
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
    $end = new DateTime($data_fim);
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
    $end = new DateTime($data_fim);
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


// ===== Consultar totais do período anterior =====
$sql_ganhos_ant = "SELECT COALESCE(SUM(valor), 0) AS total FROM ganhos WHERE usuario_id = ? AND data_ganho BETWEEN ? AND ?";
$stmt = $conexao->prepare($sql_ganhos_ant);
$stmt->bind_param("iss", $usuario_id, $data_inicio_ant, $data_fim_ant);
$stmt->execute();
$total_ganhos_ant = floatval($stmt->get_result()->fetch_assoc()['total']);

$sql_despesas_ant = "SELECT COALESCE(SUM(valor), 0) AS total FROM despesas WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?" . $filtro_categoria;
$stmt = $conexao->prepare($sql_despesas_ant);
if ($categoria_id) {
    $stmt->bind_param("issi", $usuario_id, $data_inicio_ant, $data_fim_ant, $categoria_id);
} else {
    $stmt->bind_param("iss", $usuario_id, $data_inicio_ant, $data_fim_ant);
}
$stmt->execute();
$total_despesas_ant = floatval($stmt->get_result()->fetch_assoc()['total']);


echo json_encode([
    'status' => 'success',
    'periodo' => $periodo,
    'labels' => $labels,
    'ganhos' => $ganhos_data,
    'despesas' => $despesas_data,
    'total_ganhos' => $total_ganhos,
    'total_despesas' => $total_despesas,
    'total_ganhos_anterior' => $total_ganhos_ant,
    'total_despesas_anterior' => $total_despesas_ant
]);
?>
