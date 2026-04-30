<?php
// backend/api/ganhos/create.php — Cria novo ganho com validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/GanhosValidator.php';


$usuario_id = requireAuth();


// Receber dados de FormData
$data = [
    'descricao' => $_POST['descricao'] ?? '',
    'valor' => $_POST['valor'] ?? 0,
    'data_ganho' => $_POST['data_ganho'] ?? date('Y-m-d'),
    'fixo' => $_POST['fixo'] ?? 0,
    'categoria_id' => $_POST['categoria_id'] ?? ''
];


// Validar dados contra regras de negócio
$validation = GanhosValidator::validate($data);
if (!$validation['valid']) {
    echo json_encode([
        'status' => 'error',
        'message' => $validation['errors'][0]
    ]);
    exit;
}

$descricao = $validation['data']['descricao'];
$valor = $validation['data']['valor'];
$data_ganho = $validation['data']['data_ganho'];
$fixo = $validation['data']['fixo'];
$categoria_id = $validation['data']['categoria_id'];


// Inserir ganho no banco de dados
$stmt = $conexao->prepare(
    "INSERT INTO ganhos (usuario_id, descricao, valor, data_ganho, fixo, categoria_id) 
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("isdsii", $usuario_id, $descricao, $valor, $data_ganho, $fixo, $categoria_id);

// Executar e verificar inserção
if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Ganho registrado!',
        'id' => $stmt->insert_id
    ]);
} else {
    // Erro ao inserir
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao registrar ganho.'
    ]);
}
?>