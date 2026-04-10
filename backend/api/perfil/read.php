<?php
// backend/api/perfil/read.php — Retorna dados do perfil do usuário
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
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
$stmt2 = $conexao->prepare(
    "SELECT renda_mensal, saldo_inicial, possui_investimentos, possui_patrimonio, 
            objetivo_financeiro, perfil_comportamento 
     FROM perfil_financeiro WHERE usuario_id = ?"
);
$stmt2->bind_param('i', $usuario_id);
$stmt2->execute();
$perfil = $stmt2->get_result()->fetch_assoc();

// Calcular total de ganhos
$stmtG = $conexao->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM ganhos WHERE usuario_id = ?");
$stmtG->bind_param('i', $usuario_id);
$stmtG->execute();
$totalGanhos = $stmtG->get_result()->fetch_assoc()['total'];

// Calcular total de despesas
$stmtD = $conexao->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE usuario_id = ?");
$stmtD->bind_param('i', $usuario_id);
$stmtD->execute();
$totalDespesas = $stmtD->get_result()->fetch_assoc()['total'];

// Contar registros de ganhos
$stmtCountG = $conexao->prepare("SELECT COUNT(*) as total FROM ganhos WHERE usuario_id = ?");
$stmtCountG->bind_param('i', $usuario_id);
$stmtCountG->execute();
$countGanhos = $stmtCountG->get_result()->fetch_assoc()['total'];

// Contar registros de despesas
$stmtCountD = $conexao->prepare("SELECT COUNT(*) as total FROM despesas WHERE usuario_id = ?");
$stmtCountD->bind_param('i', $usuario_id);
$stmtCountD->execute();
$countDespesas = $stmtCountD->get_result()->fetch_assoc()['total'];

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
