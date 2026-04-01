<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? 0;
$descricao = $data['descricao'] ?? '';
$valor = $data['valor'] ?? 0;
$data_despesa = $data['data_despesa'] ?? '';
$fixo = !empty($data['fixo']) ? 1 : 0;

if ($id <= 0 || empty($descricao) || $valor <= 0) {
    echo json_encode(["status" => "error", "message" => "Dados incompletos."]);
    exit;
}

$stmt = $conexao->prepare("UPDATE despesas SET descricao = ?, valor = ?, data_despesa = ?, fixo = ? WHERE id = ?");
$stmt->bind_param("sdsii", $descricao, $valor, $data_despesa, $fixo, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "Despesa atualizada!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Despesa não encontrada."]);
}
?>
