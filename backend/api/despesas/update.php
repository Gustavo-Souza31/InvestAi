<?php
// backend/api/despesas/update.php — Atualiza despesa existente com validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/DespesasValidator.php';
require_once $root . '/backend/validators/IdValidator.php';


// Autenticação
requireAuth();


// Receber dados de FormData
$id = intval($_POST['id'] ?? 0);
$data = [
    'descricao' => $_POST['descricao'] ?? '',
    'valor' => $_POST['valor'] ?? 0,
    'data_despesa' => $_POST['data_despesa'] ?? '',
    'fixo' => $_POST['fixo'] ?? 0
];


// Validar ID
$idValidation = IdValidator::validateId($id);
if (!$idValidation['valid']) {
    echo json_encode([
        'status' => 'error',
        'message' => $idValidation['errors'][0]
    ]);
    exit;
}


// Validar dados contra regras de negócio
$validation = DespesasValidator::validate($data);
if (!$validation['valid']) {
    echo json_encode([
        'status' => 'error',
        'message' => $validation['errors'][0]
    ]);
    exit;
}

$descricao = $validation['data']['descricao'];
$valor = $validation['data']['valor'];
$data_despesa = $validation['data']['data_despesa'];
$fixo = $validation['data']['fixo'];


// Atualizar despesa no banco de dados
$stmt = $conexao->prepare(
    'UPDATE despesas 
     SET descricao = ?, valor = ?, data_despesa = ?, fixo = ? 
     WHERE id = ?'
);
$stmt->bind_param('sdsii', $descricao, $valor, $data_despesa, $fixo, $id);

// Executar e verificar atualizacao
if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Despesa atualizada!'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Despesa não encontrada.'
    ]);
}
?>
