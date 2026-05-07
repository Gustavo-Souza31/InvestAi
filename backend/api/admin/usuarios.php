<?php
// backend/api/admin/usuarios.php — Lista todos os usuários cadastrados
session_start();
header('Content-Type: application/json; charset=utf-8');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/admin_middleware.php';
require_once $root . '/backend/includes/Logger.php';

requireAdmin();

$admin_id    = $_SESSION['usuario_id'];
$admin_email = $_SESSION['usuario_email'] ?? null;

$pagina  = max(1, intval($_GET['pagina'] ?? 1));
$por_pag = 30;
$offset  = ($pagina - 1) * $por_pag;
$busca   = trim($_GET['busca'] ?? '');

$where  = '';
$params = [];
$types  = '';

if ($busca) {
    $like   = '%' . $busca . '%';
    $where  = 'WHERE u.nome LIKE ? OR u.email LIKE ?';
    $params = [$like, $like];
    $types  = 'ss';
}

// Contar total
$stmt_count = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios u $where");
if ($types) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total = $stmt_count->get_result()->fetch_assoc()['total'];

// Buscar usuários
$stmt = $conexao->prepare(
    "SELECT u.id, u.nome, u.email, u.telefone, u.ativo, u.criado_em,
            pf.renda_mensal, pf.perfil_comportamento
     FROM usuarios u
     LEFT JOIN perfil_financeiro pf ON pf.usuario_id = u.id
     $where
     ORDER BY u.criado_em DESC
     LIMIT ? OFFSET ?"
);

$params_pag = array_merge($params, [$por_pag, $offset]);
$types_pag  = $types . 'ii';
$stmt->bind_param($types_pag, ...$params_pag);
$stmt->execute();
$result = $stmt->get_result();

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $row['ativo'] = (bool) $row['ativo'];
    $usuarios[]   = $row;
}

Logger::log('INFO', 'ADMIN_ACCESS', ['secao' => 'usuarios'], 'sucesso', $admin_id, $admin_email);

echo json_encode([
    'status'        => 'success',
    'usuarios'      => $usuarios,
    'total'         => (int) $total,
    'pagina'        => $pagina,
    'por_pagina'    => $por_pag,
    'total_paginas' => (int) ceil($total / $por_pag),
], JSON_UNESCAPED_UNICODE);
?>
