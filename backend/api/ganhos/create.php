<?php
require_once __DIR__ . '/../../../DataBase/conexao.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$descricao = $data['descricao'] ?? '';
$valor = $data['valor'] ?? 0;
$data_ganho = $data['data_ganho'] ?? date('Y-m-d');
$fixo = !empty($data['fixo']) ? 1 : 0;
$usuario_id = $data['usuario_id'] ?? 1;

if (empty($descricao) || $valor <= 0) {
    echo json_encode(["status" => "error", "message" => "Descrição e valor obrigatórios."]);
    exit;
}

$stmt = $conexao->prepare("INSERT INTO ganhos (usuario_id, descricao, valor, data_ganho, fixo) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isdsi", $usuario_id, $descricao, $valor, $data_ganho, $fixo);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Ganho registrado!", "id" => $stmt->insert_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Erro ao registrar ganho."]);
}
?>
