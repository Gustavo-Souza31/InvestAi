<?php
// backend/api/despesas/update.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../../includes/auth_middleware.php';
$usuario_id = requireAuth();
require_once __DIR__ . '/../../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['id'] ?? 0);
$descricao = trim($data['descricao'] ?? '');
$valor = floatval($data['valor'] ?? 0);
$data_despesa = $data['data_despesa'] ?? '';
$fixo = !empty($data['fixo']) ? 1 : 0;

if ($id <= 0 || empty($descricao) || $valor <= 0 || empty($data_despesa)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Dados incompletos."]);
    exit;
}

$stmt = $conexao->prepare("UPDATE despesas SET descricao = ?, valor = ?, data_despesa = ?, fixo = ? WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("sdsiii", $descricao, $valor, $data_despesa, $fixo, $id, $usuario_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Despesa atualizada!"]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Despesa não encontrada."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao atualizar."]);
}

$stmt->close();
$conexao->close();
?>
