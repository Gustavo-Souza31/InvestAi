<?php
// backend/api/aportes/update.php — Atualiza aporte e ajusta valor_guardado da meta (delta)
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';

$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados de FormData
$id          = intval($_POST['id'] ?? 0);
$valor       = floatval($_POST['valor'] ?? 0);
$data_aporte = $_POST['data_aporte'] ?? '';

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID do aporte é obrigatório.']);
    exit;
}

if ($valor <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valor deve ser maior que 0.']);
    exit;
}

if (!$data_aporte) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data é obrigatória.']);
    exit;
}


// Buscar aporte existente
$stmtBusca = $conexao->prepare("SELECT id, valor AS valor_antigo, meta_id FROM aportes WHERE id = ? AND usuario_id = ?");
$stmtBusca->bind_param("ii", $id, $usuario_id);
$stmtBusca->execute();
$aporte = $stmtBusca->get_result()->fetch_assoc();

if (!$aporte) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Aporte não encontrado.']);
    exit;
}

$meta_id      = $aporte['meta_id'];
$valor_antigo = floatval($aporte['valor_antigo']);


// Transação: atualizar aporte e ajustar valor_guardado (delta)
$conexao->begin_transaction();
try {
    $stmtAporte = $conexao->prepare("UPDATE aportes SET valor = ?, data_aporte = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND usuario_id = ?");
    $stmtAporte->bind_param("dsii", $valor, $data_aporte, $id, $usuario_id);
    if (!$stmtAporte->execute()) throw new Exception('Erro ao atualizar aporte');

    // Aplica delta: remove valor antigo, adiciona novo
    $stmtMeta = $conexao->prepare("UPDATE metas SET valor_guardado = valor_guardado - ? + ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND usuario_id = ?");
    $stmtMeta->bind_param("ddii", $valor_antigo, $valor, $meta_id, $usuario_id);
    if (!$stmtMeta->execute()) throw new Exception('Erro ao atualizar meta');

    $conexao->commit();

    Logger::log('INFO', 'APORTE_UPDATED', ['id' => $id, 'valor_antigo' => $valor_antigo, 'valor_novo' => $valor], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Aporte atualizado.']);
} catch (Exception $e) {
    $conexao->rollback();
    Logger::log('ERROR', 'APORTE_UPDATED', ['id' => $id, 'erro' => $e->getMessage()], 'falha', $usuario_id, $usuario_email);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar aporte.']);
}
?>
