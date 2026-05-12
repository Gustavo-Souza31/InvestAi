<?php
// backend/api/aportes/delete.php — Exclui aporte e reverte valor_guardado da meta (transação)
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';

$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados de FormData
$id = intval($_POST['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID do aporte é obrigatório.']);
    exit;
}


// Buscar aporte para obter valor e meta_id
$stmtBusca = $conexao->prepare("SELECT id, valor, meta_id FROM aportes WHERE id = ? AND usuario_id = ?");
$stmtBusca->bind_param("ii", $id, $usuario_id);
$stmtBusca->execute();
$aporte = $stmtBusca->get_result()->fetch_assoc();

if (!$aporte) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Aporte não encontrado.']);
    exit;
}

$valor   = floatval($aporte['valor']);
$meta_id = $aporte['meta_id'];


// Transação: excluir aporte e reverter valor_guardado da meta
$conexao->begin_transaction();
try {
    $stmtAporte = $conexao->prepare("DELETE FROM aportes WHERE id = ? AND usuario_id = ?");
    $stmtAporte->bind_param("ii", $id, $usuario_id);
    if (!$stmtAporte->execute() || $stmtAporte->affected_rows === 0) throw new Exception('Erro ao excluir aporte');

    // Reverte valor na meta (garante mínimo 0)
    $stmtMeta = $conexao->prepare("UPDATE metas SET valor_guardado = GREATEST(0, valor_guardado - ?), atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND usuario_id = ?");
    $stmtMeta->bind_param("dii", $valor, $meta_id, $usuario_id);
    if (!$stmtMeta->execute()) throw new Exception('Erro ao reverter meta');

    $conexao->commit();

    Logger::log('INFO', 'APORTE_DELETED', ['id' => $id, 'valor' => $valor, 'meta_id' => $meta_id], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Aporte excluído.']);
} catch (Exception $e) {
    $conexao->rollback();
    Logger::log('ERROR', 'APORTE_DELETED', ['id' => $id, 'erro' => $e->getMessage()], 'falha', $usuario_id, $usuario_email);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir aporte.']);
}
?>
