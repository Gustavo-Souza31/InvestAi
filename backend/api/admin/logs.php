<?php
// backend/api/admin/logs.php — Lista logs com filtros e paginação
session_start();
header('Content-Type: application/json; charset=utf-8');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/admin_middleware.php';
require_once $root . '/backend/includes/Logger.php';

requireAdmin();

$usuario_id    = $_SESSION['usuario_id'];
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Parâmetros de filtro e paginação
$nivel    = $_GET['nivel']    ?? '';
$acao     = $_GET['acao']     ?? '';
$uid      = intval($_GET['usuario_id'] ?? 0);
$de       = $_GET['de']       ?? '';
$ate      = $_GET['ate']      ?? '';
$pagina   = max(1, intval($_GET['pagina'] ?? 1));
$por_pag  = 50;
$offset   = ($pagina - 1) * $por_pag;

$niveis_validos = ['INFO', 'WARN', 'ERROR', 'DEBUG'];

// Montar WHERE dinâmico
$where   = [];
$params  = [];
$types   = '';

if ($nivel && in_array($nivel, $niveis_validos)) {
    $where[]  = 'nivel = ?';
    $params[] = $nivel;
    $types   .= 's';
}
if ($acao) {
    $like     = '%' . $acao . '%';
    $where[]  = 'acao LIKE ?';
    $params[] = $like;
    $types   .= 's';
}
if ($uid > 0) {
    $where[]  = 'usuario_id = ?';
    $params[] = $uid;
    $types   .= 'i';
}
if ($de) {
    $where[]  = 'timestamp >= ?';
    $params[] = $de . ' 00:00:00';
    $types   .= 's';
}
if ($ate) {
    $where[]  = 'timestamp <= ?';
    $params[] = $ate . ' 23:59:59';
    $types   .= 's';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';


// Contar total de registros para paginação
$stmt_count = $conexao->prepare("SELECT COUNT(*) as total FROM logs $where_sql");
if ($types && $params) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total = $stmt_count->get_result()->fetch_assoc()['total'];


// Buscar logs paginados
$stmt = $conexao->prepare(
    "SELECT id, timestamp, nivel, usuario_id, usuario_email, ip, acao, detalhes, status
     FROM logs
     $where_sql
     ORDER BY timestamp DESC
     LIMIT ? OFFSET ?"
);

$params_pag  = array_merge($params, [$por_pag, $offset]);
$types_pag   = $types . 'ii';
$stmt->bind_param($types_pag, ...$params_pag);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $row['detalhes'] = $row['detalhes'] ? json_decode($row['detalhes'], true) : null;
    $logs[] = $row;
}

Logger::log('INFO', 'ADMIN_ACCESS', ['secao' => 'logs', 'filtros' => ['nivel' => $nivel, 'acao' => $acao]], 'sucesso', $usuario_id, $usuario_email);

echo json_encode([
    'status'      => 'success',
    'logs'        => $logs,
    'total'       => (int) $total,
    'pagina'      => $pagina,
    'por_pagina'  => $por_pag,
    'total_paginas' => (int) ceil($total / $por_pag),
], JSON_UNESCAPED_UNICODE);
?>
