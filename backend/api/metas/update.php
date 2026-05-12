<?php
// backend/api/metas/update.php — Atualiza meta
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/validators/MetasValidator.php';


// Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados do body JSON
$body    = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$meta_id = isset($body['id']) ? intval($body['id']) : null;

if (!$meta_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID da meta é obrigatório.']);
    exit;
}


// Validar dados contra regras de negócio
$validation = MetasValidator::validate($body ?? []);
if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$nome        = $validation['data']['nome'];
$valor_total = $validation['data']['valor_total'];
$prazo       = $validation['data']['prazo'];


// Atualizar meta no banco de dados
$stmt = $conexao->prepare("UPDATE metas SET nome = ?, valor_total = ?, prazo = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("sdssi", $nome, $valor_total, $prazo, $meta_id, $usuario_id);


// Executar e verificar atualização
if ($stmt->execute()) {
    Logger::log('INFO', 'META_UPDATED', ['id' => $meta_id, 'nome' => $nome], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Meta atualizada.']);
} else {
    Logger::log('ERROR', 'META_UPDATED', ['id' => $meta_id], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar meta.']);
}
?>
