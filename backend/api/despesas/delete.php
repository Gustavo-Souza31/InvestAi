<?php
// backend/api/despesas/delete.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../../includes/auth_middleware.php';
$usuario_id = requireAuth();
require_once __DIR__ . '/../../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

$stmt = $conexao->prepare("DELETE FROM despesas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $id, $usuario_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Despesa excluída!"]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Despesa não encontrada."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao excluir."]);
}

$stmt->close();
$conexao->close();
?>
