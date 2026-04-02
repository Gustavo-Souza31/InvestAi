<?php
header('Content-Type: application/json');
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/DespesasValidator.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$usuario_id = requireAuth();

// Receber dados de POST (FormData)
$data = [
    'descricao' => $_POST['descricao'] ?? '',
    'valor' => $_POST['valor'] ?? 0,
    'data_despesa' => $_POST['data_despesa'] ?? date('Y-m-d'),
    'fixo' => $_POST['fixo'] ?? 0
];

// Validar
$validation = DespesasValidator::validate($data);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$descricao = $validation['data']['descricao'];
$valor = $validation['data']['valor'];
$data_despesa = $validation['data']['data_despesa'];
$fixo = $validation['data']['fixo'];

// Inserir
$stmt = $conexao->prepare("INSERT INTO despesas (usuario_id, descricao, valor, data_despesa, fixo) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isdsi", $usuario_id, $descricao, $valor, $data_despesa, $fixo);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Despesa criada!', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao criar despesa.']);
}
?>
