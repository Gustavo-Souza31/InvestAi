<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$id = $_POST['id'] ?? 0;

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

$stmt = $conexao->prepare("DELETE FROM ganhos WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "Ganho excluído!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Ganho não encontrado."]);
}
?>
