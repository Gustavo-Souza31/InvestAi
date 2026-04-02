<?php
header('Content-Type: application/json');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/GanhosValidator.php';
require_once $root . '/backend/validators/IdValidator.php';

requireAuth();

// Receber dados
$id = intval($_POST['id'] ?? 0);
$data = [
    'descricao' => $_POST['descricao'] ?? '',
    'valor' => $_POST['valor'] ?? 0,
    'data_ganho' => $_POST['data_ganho'] ?? '',
    'fixo' => $_POST['fixo'] ?? 0
];

// Validar ID
$idValidation = IdValidator::validateId($id);
if (!$idValidation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $idValidation['errors'][0]]);
    exit;
}

// Validar dados
$validation = GanhosValidator::validate($data);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$descricao = $validation['data']['descricao'];
$valor = $validation['data']['valor'];
$data_ganho = $validation['data']['data_ganho'];
$fixo = $validation['data']['fixo'];

// Atualizar
$stmt = $conexao->prepare('UPDATE ganhos SET descricao = ?, valor = ?, data_ganho = ?, fixo = ? WHERE id = ?');
$stmt->bind_param('sdsii', $descricao, $valor, $data_ganho, $fixo, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Ganho atualizado!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ganho não encontrado.']);
}
?>
