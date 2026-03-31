<?php
require_once __DIR__ . '/../../../DataBase/conexao.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? 0;
$descricao = $data['descricao'] ?? '';
$valor = $data['valor'] ?? 0;
$data_ganho = $data['data_ganho'] ?? '';
$fixo = !empty($data['fixo']) ? 1 : 0;

if ($id <= 0 || empty($descricao) || $valor <= 0) {
    echo json_encode(["status" => "error", "message" => "Dados incompletos."]);
    exit;
}

$stmt = $conexao->prepare("UPDATE ganhos SET descricao = ?, valor = ?, data_ganho = ?, fixo = ? WHERE id = ?");
$stmt->bind_param("sdsii", $descricao, $valor, $data_ganho, $fixo, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "Ganho atualizado!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Ganho não encontrado."]);
}
?>
