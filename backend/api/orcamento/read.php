<?php
/**
 * backend/api/orcamento/read.php
 * Retorna os orçamentos do mês atual com o gasto real de cada categoria.
 */
header('Content-Type: application/json; charset=utf-8');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';

$usuario_id = requireAuth();
$mes = intval($_GET['mes'] ?? date('n'));
$ano = intval($_GET['ano'] ?? date('Y'));

// Busca limites cadastrados para o mês
$stmt = $conexao->prepare(
    "SELECT categoria_nome, limite_mensal
     FROM orcamento_categorias
     WHERE usuario_id = ? AND mes = ? AND ano = ?
     ORDER BY categoria_nome ASC"
);
$stmt->bind_param('iii', $usuario_id, $mes, $ano);
$stmt->execute();
$res = $stmt->get_result();

$orcamentos = [];
while ($row = $res->fetch_assoc()) {
    $orcamentos[$row['categoria_nome']] = [
        'categoria'   => $row['categoria_nome'],
        'limite'      => floatval($row['limite_mensal']),
        'gasto_atual' => 0.0,
        'percentual'  => 0.0,
    ];
}

// Se não há orçamentos, retorna lista vazia
if (empty($orcamentos)) {
    echo json_encode(['status' => 'success', 'orcamentos' => []]);
    exit;
}

// Busca o gasto real por categoria no mês (usando categoria_nome das despesas via JOIN)
$inicio = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$fim    = date('Y-m-t', strtotime($inicio));

$stmt2 = $conexao->prepare(
    "SELECT c.nome AS categoria_nome, COALESCE(SUM(d.valor), 0) AS total_gasto
     FROM despesas d
     LEFT JOIN categorias c ON d.categoria_id = c.id
     WHERE d.usuario_id = ? AND d.data_despesa BETWEEN ? AND ?
     GROUP BY c.nome"
);
$stmt2->bind_param('iss', $usuario_id, $inicio, $fim);
$stmt2->execute();
$res2 = $stmt2->get_result();

while ($row2 = $res2->fetch_assoc()) {
    $cat = $row2['categoria_nome'] ?? 'Sem Categoria';
    if (isset($orcamentos[$cat])) {
        $gasto = floatval($row2['total_gasto']);
        $orcamentos[$cat]['gasto_atual'] = $gasto;
        $limite = $orcamentos[$cat]['limite'];
        $orcamentos[$cat]['percentual'] = $limite > 0 ? min(round(($gasto / $limite) * 100, 1), 100) : 0;
    }
}

echo json_encode([
    'status'     => 'success',
    'orcamentos' => array_values($orcamentos),
    'mes'        => $mes,
    'ano'        => $ano,
], JSON_UNESCAPED_UNICODE);
