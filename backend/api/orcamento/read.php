<?php
// backend/api/orcamento/read.php — Retorna orçamentos do mês com o gasto real de cada categoria
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';


$usuario_id = requireAuth();


// Receber parâmetros de query string
$mes = intval($_GET['mes'] ?? date('n'));
$ano = intval($_GET['ano'] ?? date('Y'));


// Buscar limites cadastrados para o mês
$stmt = $conexao->prepare(
    "SELECT oc.categoria_id, c.nome as categoria, oc.limite_mensal
     FROM orcamento_categorias oc
     JOIN categorias c ON oc.categoria_id = c.id
     WHERE oc.usuario_id = ? AND oc.mes = ? AND oc.ano = ?
     ORDER BY c.nome ASC"
);
$stmt->bind_param('iii', $usuario_id, $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$orcamentos = [];
while ($row = $result->fetch_assoc()) {
    $orcamentos[$row['categoria_id']] = [
        'categoria_id' => $row['categoria_id'],
        'categoria'    => $row['categoria'],
        'limite'       => floatval($row['limite_mensal']),
        'gasto_atual'  => 0.0,
        'percentual'   => 0.0,
    ];
}


// Retornar lista vazia se não há orçamentos
if (empty($orcamentos)) {
    echo json_encode(['status' => 'success', 'orcamentos' => []]);
    exit;
}


// Buscar o gasto real por categoria no mês
$inicio = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$fim    = date('Y-m-t', strtotime($inicio));

$stmt2 = $conexao->prepare(
    "SELECT d.categoria_id, COALESCE(SUM(d.valor), 0) AS total_gasto
     FROM despesas d
     WHERE d.usuario_id = ? AND d.data_despesa BETWEEN ? AND ? AND d.categoria_id IS NOT NULL
     GROUP BY d.categoria_id"
);
$stmt2->bind_param('iss', $usuario_id, $inicio, $fim);
$stmt2->execute();
$result2 = $stmt2->get_result();

while ($row2 = $result2->fetch_assoc()) {
    $cat_id = $row2['categoria_id'];
    if (isset($orcamentos[$cat_id])) {
        $gasto = floatval($row2['total_gasto']);
        $limite = $orcamentos[$cat_id]['limite'];
        $orcamentos[$cat_id]['gasto_atual'] = $gasto;
        $orcamentos[$cat_id]['percentual']  = $limite > 0 ? round(($gasto / $limite) * 100, 1) : 0;
    }
}


// Retornar resposta
echo json_encode([
    'status'     => 'success',
    'orcamentos' => array_values($orcamentos),
    'mes'        => $mes,
    'ano'        => $ano,
], JSON_UNESCAPED_UNICODE);
?>
