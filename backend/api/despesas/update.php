<?php
// backend/api/despesas/update.php — Atualiza despesa existente com validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/validators/DespesasValidator.php';
require_once $root . '/backend/validators/IdValidator.php';


// Autenticação
$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados de FormData
$id   = intval($_POST['id'] ?? 0);
$data = [
    'descricao'    => $_POST['descricao']    ?? '',
    'valor'        => $_POST['valor']        ?? 0,
    'data_despesa' => $_POST['data_despesa'] ?? '',
    'fixo'         => $_POST['fixo']         ?? 0,
    'categoria_id' => $_POST['categoria_id'] ?? ''
];


// Validar ID
$idValidation = IdValidator::validateId($id);
if (!$idValidation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $idValidation['errors'][0]]);
    exit;
}


// Validar dados contra regras de negócio
$validation = DespesasValidator::validate($data);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$descricao       = $validation['data']['descricao'];
$valor           = $validation['data']['valor'];
$data_despesa    = $validation['data']['data_despesa'];
$fixo            = $validation['data']['fixo'];
$categoria_input = $validation['data']['categoria_id'];
$categoria_id    = !empty($categoria_input) ? intval($categoria_input) : null;


// Atualizar despesa no banco de dados
$stmt = $conexao->prepare(
    'UPDATE despesas SET descricao = ?, valor = ?, data_despesa = ?, fixo = ?, categoria_id = ? WHERE id = ?'
);
$stmt->bind_param('sdsiii', $descricao, $valor, $data_despesa, $fixo, $categoria_id, $id);


// Executar e verificar atualização
if ($stmt->execute()) {
    Logger::log('INFO', 'DESPESA_UPDATED', ['id' => $id, 'valor' => $valor], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Despesa atualizada!']);
} else {
    Logger::log('ERROR', 'DESPESA_UPDATED', ['id' => $id], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar despesa.']);
}
?>
