<?php
require_once __DIR__ . '/../../../DataBase/conexao.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? 0;

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

$stmt = $conexao->prepare("DELETE FROM despesas WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "Despesa excluída!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Despesa não encontrada."]);
}
?>
