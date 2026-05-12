<?php
// backend/api/aportes/create.php — Registra aporte em uma meta (transação atômica)
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';

$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados do body JSON
$body        = json_decode(file_get_contents('php://input'), true);
$meta_id     = isset($body['meta_id']) ? intval($body['meta_id']) : null;
$valor       = floatval($body['valor'] ?? 0);
$data_aporte = $body['data_aporte'] ?? date('Y-m-d');

if (!$meta_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID da meta é obrigatório.']);
    exit;
}

if ($valor <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valor do aporte deve ser maior que 0.']);
    exit;
}


// Validar existência da meta
$stmt = $conexao->prepare("SELECT id, valor_guardado FROM metas WHERE id = ? AND usuario_id = ? AND ativo = 1");
$stmt->bind_param("ii", $meta_id, $usuario_id);
$stmt->execute();
$meta = $stmt->get_result()->fetch_assoc();

if (!$meta) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Meta não encontrada.']);
    exit;
}


// Transação: inserir aporte e atualizar valor_guardado
$conexao->begin_transaction();
try {
    $stmtAporte = $conexao->prepare("INSERT INTO aportes (usuario_id, meta_id, valor, data_aporte) VALUES (?, ?, ?, ?)");
    $stmtAporte->bind_param("iids", $usuario_id, $meta_id, $valor, $data_aporte);
    if (!$stmtAporte->execute()) throw new Exception('Erro ao inserir aporte');

    $novo_guardado = floatval($meta['valor_guardado']) + $valor;
    $stmtMeta = $conexao->prepare("UPDATE metas SET valor_guardado = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND usuario_id = ?");
    $stmtMeta->bind_param("dii", $novo_guardado, $meta_id, $usuario_id);
    if (!$stmtMeta->execute()) throw new Exception('Erro ao atualizar meta');

    $conexao->commit();

    Logger::log('INFO', 'APORTE_CREATED', ['meta_id' => $meta_id, 'valor' => $valor], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Aporte registrado.', 'novo_valor_guardado' => $novo_guardado]);
} catch (Exception $e) {
    $conexao->rollback();
    Logger::log('ERROR', 'APORTE_CREATED', ['meta_id' => $meta_id, 'erro' => $e->getMessage()], 'falha', $usuario_id, $usuario_email);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao registrar aporte.']);
}
?>
