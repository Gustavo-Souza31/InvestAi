<?php
// backend/api/ganhos/read.php — Retorna lista de ganhos do usuário autenticado
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';


// Autenticação
$usuario_id = requireAuth();


// Consultar ganhos do usuário
$stmt = $conexao->prepare(
    "SELECT g.*, c.nome as categoria_nome 
     FROM ganhos g 
     LEFT JOIN categorias c ON g.categoria_id = c.id 
     WHERE g.usuario_id = ? 
     ORDER BY g.data_ganho DESC"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$ganhos = [];
while ($row = $result->fetch_assoc()) {
    $ganhos[] = $row;
}


// Retornar resposta
echo json_encode([
    "status" => "success",
    "ganhos" => $ganhos
]);
?>