<?php
// backend/api/ganhos/create.php — Cria novo ganho com validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/validators/GanhosValidator.php';


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados de FormData
$data = [
    'descricao'  => $_POST['descricao']  ?? '',
    'valor'      => $_POST['valor']      ?? 0,
    'data_ganho' => $_POST['data_ganho'] ?? date('Y-m-d'),
    'fixo'       => $_POST['fixo']       ?? 0,
    'categoria_id' => $_POST['categoria_id'] ?? ''
];


// Validar dados contra regras de negócio
$validation = GanhosValidator::validate($data);
if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$descricao       = $validation['data']['descricao'];
$valor           = $validation['data']['valor'];
$data_ganho      = $validation['data']['data_ganho'];
$fixo            = $validation['data']['fixo'];
$categoria_input = $validation['data']['categoria_id'];
$categoria_id    = !empty($categoria_input) ? intval($categoria_input) : null;


// Inserir ganho no banco de dados
$stmt = $conexao->prepare(
    "INSERT INTO ganhos (usuario_id, descricao, valor, data_ganho, fixo, categoria_id)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("isdsii", $usuario_id, $descricao, $valor, $data_ganho, $fixo, $categoria_id);


// Executar e verificar inserção
if ($stmt->execute()) {
    $novo_id = $stmt->insert_id;
    Logger::log('INFO', 'GANHO_CREATED', ['id' => $novo_id, 'valor' => $valor, 'descricao' => $descricao], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Ganho registrado!', 'id' => $novo_id]);
} else {
    Logger::log('ERROR', 'GANHO_CREATED', ['descricao' => $descricao], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao registrar ganho.']);
}
?>
