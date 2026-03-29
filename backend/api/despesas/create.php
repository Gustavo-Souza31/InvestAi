<?php
// backend/api/despesas/create.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../../includes/auth_middleware.php';
$usuario_id = requireAuth();
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$descricao = trim($data['descricao'] ?? '');
$valor = floatval($data['valor'] ?? 0);
$data_despesa = $data['data_despesa'] ?? date('Y-m-d');
$fixo = !empty($data['fixo']) ? 1 : 0;

if (empty($descricao) || $valor <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Descrição e valor são obrigatórios."]);
    exit;
}

$stmt = $conexao->prepare("INSERT INTO despesas (usuario_id, descricao, valor, data_despesa, fixo) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isdsi", $usuario_id, $descricao, $valor, $data_despesa, $fixo);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Despesa criada!",
        "id" => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao criar despesa."]);
}

$stmt->close();
$conexao->close();
?>
