<?php
header('Content-Type: application/json');
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/GanhosValidator.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$usuario_id = requireAuth();

// Receber dados
$data = [
    'descricao' => $_POST['descricao'] ?? '',
    'valor' => $_POST['valor'] ?? 0,
    'data_ganho' => $_POST['data_ganho'] ?? date('Y-m-d'),
    'fixo' => $_POST['fixo'] ?? 0
];

// Validar
$validation = GanhosValidator::validate($data);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$descricao = $validation['data']['descricao'];
$valor = $validation['data']['valor'];
$data_ganho = $validation['data']['data_ganho'];
$fixo = $validation['data']['fixo'];

// Inserir
$stmt = $conexao->prepare("INSERT INTO ganhos (usuario_id, descricao, valor, data_ganho, fixo) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isdsi", $usuario_id, $descricao, $valor, $data_ganho, $fixo);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Ganho registrado!', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao registrar ganho.']);
}
?>
