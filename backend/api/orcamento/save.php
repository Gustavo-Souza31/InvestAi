<?php
/**
 * backend/api/orcamento/save.php
 * Salva ou atualiza o limite mensal de uma categoria de despesa.
 */
header('Content-Type: application/json; charset=utf-8');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';

$usuario_id = requireAuth();

$body = json_decode(file_get_contents('php://input'), true);
$categoria = trim($body['categoria'] ?? '');
$limite     = floatval($body['limite'] ?? 0);
$mes        = intval($body['mes']     ?? date('n'));
$ano        = intval($body['ano']     ?? date('Y'));

// Validações
if ($categoria === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Categoria obrigatória.']);
    exit;
}
if ($limite <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'O limite deve ser maior que zero.']);
    exit;
}
if ($mes < 1 || $mes > 12) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mês inválido.']);
    exit;
}

$stmt = $conexao->prepare(
    "INSERT INTO orcamento_categorias (usuario_id, categoria_nome, limite_mensal, mes, ano)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE limite_mensal = VALUES(limite_mensal), atualizado_em = CURRENT_TIMESTAMP"
);
$stmt->bind_param('isdii', $usuario_id, $categoria, $limite, $mes, $ano);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Limite definido com sucesso!']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar no banco.']);
}
?>
