<?php
// backend/api/perfil/read.php — Retorna dados do perfil do usuário
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';


$usuario_id = requireAuth();


// Buscar dados pessoais completos
$stmt = $conexao->prepare(
    "SELECT id, nome, email, cpf, telefone, criado_em FROM usuarios WHERE id = ?"
);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Usuário não encontrado.']);
    exit;
}


// Buscar perfil financeiro completo
$stmtPerfil = $conexao->prepare(
    "SELECT renda_mensal, saldo_inicial, possui_investimentos, possui_patrimonio,
            objetivo_financeiro, perfil_comportamento
     FROM perfil_financeiro WHERE usuario_id = ?"
);
$stmtPerfil->bind_param('i', $usuario_id);
$stmtPerfil->execute();
$perfil = $stmtPerfil->get_result()->fetch_assoc();


// Calcular totais e contagens
$stmtTotalGanhos = $conexao->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM ganhos WHERE usuario_id = ?");
$stmtTotalGanhos->bind_param('i', $usuario_id);
$stmtTotalGanhos->execute();
$totalGanhos = $stmtTotalGanhos->get_result()->fetch_assoc()['total'];

$stmtTotalDespesas = $conexao->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE usuario_id = ?");
$stmtTotalDespesas->bind_param('i', $usuario_id);
$stmtTotalDespesas->execute();
$totalDespesas = $stmtTotalDespesas->get_result()->fetch_assoc()['total'];

$stmtCountGanhos = $conexao->prepare("SELECT COUNT(*) as total FROM ganhos WHERE usuario_id = ?");
$stmtCountGanhos->bind_param('i', $usuario_id);
$stmtCountGanhos->execute();
$countGanhos = $stmtCountGanhos->get_result()->fetch_assoc()['total'];

$stmtCountDespesas = $conexao->prepare("SELECT COUNT(*) as total FROM despesas WHERE usuario_id = ?");
$stmtCountDespesas->bind_param('i', $usuario_id);
$stmtCountDespesas->execute();
$countDespesas = $stmtCountDespesas->get_result()->fetch_assoc()['total'];

// Retornar dados completos do perfil
echo json_encode([
    'status' => 'success',
    'usuario' => [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'cpf' => $usuario['cpf'],
        'telefone' => $usuario['telefone'],
        'criado_em' => $usuario['criado_em']
    ],
    'perfil_financeiro' => $perfil ? [
        'renda_mensal' => $perfil['renda_mensal'],
        'saldo_inicial' => $perfil['saldo_inicial'],
        'possui_investimentos' => (bool)$perfil['possui_investimentos'],
        'possui_patrimonio' => (bool)$perfil['possui_patrimonio'],
        'objetivo_financeiro' => $perfil['objetivo_financeiro'],
        'perfil_comportamento' => $perfil['perfil_comportamento']
    ] : null,
    'estatisticas' => [
        'total_ganhos' => floatval($totalGanhos),
        'total_despesas' => floatval($totalDespesas),
        'count_ganhos' => intval($countGanhos),
        'count_despesas' => intval($countDespesas)
    ]
]);
?>
