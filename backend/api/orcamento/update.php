<?php
// backend/api/orcamento/update.php — Atualiza o limite mensal de uma categoria de despesa existente
header('Content-Type: application/json; charset=utf-8');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/OrcamentoValidator.php';


// Autenticação
$usuario_id = requireAuth();


// Receber dados do body JSON
$body       = json_decode(file_get_contents('php://input'), true);
$validation = OrcamentoValidator::validate($body ?? []);


// Validar dados contra regras de negócio
if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$categoria_id = $validation['data']['categoria_id'];
$limite       = $validation['data']['limite'];
$mes          = $validation['data']['mes'];
$ano          = $validation['data']['ano'];


// Verificar se categoria existe e pertence ao usuário
$stmt_check = $conexao->prepare(
    "SELECT id FROM categorias WHERE id = ? AND tipo = 'despesa' AND (usuario_id IS NULL OR usuario_id = ?)"
);
$stmt_check->bind_param('ii', $categoria_id, $usuario_id);
$stmt_check->execute();
if (!$stmt_check->get_result()->fetch_assoc()) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Categoria inválida.']);
    exit;
}


// Atualizar orçamento no banco de dados
$stmt = $conexao->prepare(
    "INSERT INTO orcamento_categorias (usuario_id, categoria_id, limite_mensal, mes, ano)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE limite_mensal = VALUES(limite_mensal), atualizado_em = CURRENT_TIMESTAMP"
);
$stmt->bind_param('iidii', $usuario_id, $categoria_id, $limite, $mes, $ano);


// Executar e verificar atualização
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Limite atualizado com sucesso!']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar no banco.']);
}
?>
