<?php
// backend/api/metas/create.php — Cria nova meta
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
$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;


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


// Inserir meta no banco de dados
$stmt = $conexao->prepare(
    "INSERT INTO metas (usuario_id, nome, valor_total, valor_guardado, prazo) VALUES (?, ?, ?, 0.00, ?)"
);
$stmt->bind_param("isds", $usuario_id, $nome, $valor_total, $prazo);


// Executar e verificar inserção
if ($stmt->execute()) {
    $novo_id = $stmt->insert_id;
    Logger::log('INFO', 'META_CREATED', ['id' => $novo_id, 'nome' => $nome, 'valor_total' => $valor_total], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Meta criada!', 'id' => $novo_id]);
} else {
    Logger::log('ERROR', 'META_CREATED', ['nome' => $nome], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao criar meta.']);
}
?>
