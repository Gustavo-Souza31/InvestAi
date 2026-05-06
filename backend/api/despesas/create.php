<?php
// backend/api/despesas/create.php — Cria nova despesa com validação
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/DespesasValidator.php';


// Autenticação
$usuario_id = requireAuth();


// Receber dados de POST (FormData)
$data = [
    'descricao' => $_POST['descricao'] ?? '',
    'valor' => $_POST['valor'] ?? 0,
    'data_despesa' => $_POST['data_despesa'] ?? date('Y-m-d'),
    'fixo' => $_POST['fixo'] ?? 0,
    'categoria_id' => $_POST['categoria_id'] ?? ''
];


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
$categoria_input = $validation['data']['categoria_id'];

$categoria_id = !empty($categoria_input) ? intval($categoria_input) : null;

// Inserir despesa no banco de dados
$stmt = $conexao->prepare(
    "INSERT INTO despesas (usuario_id, descricao, valor, data_despesa, fixo, categoria_id) 
     VALUES (?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param("isdsii", $usuario_id, $descricao, $valor, $data_despesa, $fixo, $categoria_id);


// Executar e verificar inserção
if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Despesa criada!',
        'id' => $stmt->insert_id
    ]);

} else {
    // Erro ao inserir
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao criar despesa.'
    ]);
}
?>