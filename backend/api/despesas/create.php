<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$descricao = $data['descricao'] ?? '';
$valor = $data['valor'] ?? 0;
$data_despesa = $data['data_despesa'] ?? date('Y-m-d');
$fixo = !empty($data['fixo']) ? 1 : 0;
$usuario_id = $data['usuario_id'] ?? 1;

if (empty($descricao) || $valor <= 0) {
    echo json_encode(["status" => "error", "message" => "Descrição e valor obrigatórios."]);
    exit;
}

$stmt = $conexao->prepare("INSERT INTO despesas (usuario_id, descricao, valor, data_despesa, fixo) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isdsi", $usuario_id, $descricao, $valor, $data_despesa, $fixo);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Despesa criada!", "id" => $stmt->insert_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Erro ao criar despesa."]);
}
?>
