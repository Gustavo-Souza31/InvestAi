<?php
// backend/api/dashboard/dados.php — Retorna dados financeiros consolidados do usuário
session_start();
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';


// Autenticação
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
        $data_inicio = date('Y-m-d', strtotime('-1 month'));
}


// Buscar dados pessoais do usuário
$stmt = $conexao->prepare(
    "SELECT nome FROM usuarios WHERE id = ?"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();


// Buscar perfil financeiro
$stmt = $conexao->prepare(
    "SELECT saldo_inicial, renda_mensal, objetivo_financeiro 
     FROM perfil_financeiro WHERE usuario_id = ?"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc();


// Calcular total de ganhos para o período selecionado
if ($periodo === 'all') {
    $stmt_ganhos = $conexao->prepare(
        "SELECT COALESCE(SUM(valor), 0) as total 
         FROM ganhos WHERE usuario_id = ?"
    );
    $stmt_ganhos->bind_param("i", $usuario_id);
} else {
    $stmt_ganhos = $conexao->prepare(
        "SELECT COALESCE(SUM(valor), 0) as total 
         FROM ganhos WHERE usuario_id = ? AND data_ganho BETWEEN ? AND ?"
    );
    $stmt_ganhos->bind_param("iss", $usuario_id, $data_inicio, $hoje);
}
$stmt_ganhos->execute();
$total_ganhos = $stmt_ganhos->get_result()->fetch_assoc()['total'];


// ===== Despesas =====
// Calcular total de despesas para o período selecionado
if ($periodo === 'all') {
    $stmt_despesas = $conexao->prepare(
        "SELECT COALESCE(SUM(valor), 0) as total 
         FROM despesas WHERE usuario_id = ?"
    );
    $stmt_despesas->bind_param("i", $usuario_id);
} else {
    $stmt_despesas = $conexao->prepare(
        "SELECT COALESCE(SUM(valor), 0) as total 
         FROM despesas WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?"
    );
    $stmt_despesas->bind_param("iss", $usuario_id, $data_inicio, $hoje);
}
$stmt_despesas->execute();
$total_despesas = $stmt_despesas->get_result()->fetch_assoc()['total'];


// Calcular saldo atual (saldo_inicial + ganhos - despesas)
$saldo_atual = ($perfil['saldo_inicial'] ?? 0) + $total_ganhos - $total_despesas;


// Retornar dados consolidados
echo json_encode([
    'status' => 'success',
    'usuario' => [
        'nome' => $usuario['nome'] ?? 'Usuário'
    ],
    'financeiro' => [
        'saldo_inicial' => floatval($perfil['saldo_inicial'] ?? 0),
        'saldo_atual' => floatval($saldo_atual),
        'renda_mensal' => floatval($perfil['renda_mensal'] ?? 0),
        'objetivo_financeiro' => $perfil['objetivo_financeiro'] ?? 'Não definido',
        'total_ganhos' => floatval($total_ganhos),
        'total_despesas' => floatval($total_despesas)
    ]
]);
?>