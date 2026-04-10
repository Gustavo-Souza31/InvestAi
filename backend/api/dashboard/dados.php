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


// Calcular total de ganhos
$stmt = $conexao->prepare(
    "SELECT COALESCE(SUM(valor), 0) as total 
     FROM ganhos WHERE usuario_id = ?"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$total_ganhos = $stmt->get_result()->fetch_assoc()['total'];

// Calcular total de despesas
$stmt = $conexao->prepare(
    "SELECT COALESCE(SUM(valor), 0) as total 
     FROM despesas WHERE usuario_id = ?"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$total_despesas = $stmt->get_result()->fetch_assoc()['total'];


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
