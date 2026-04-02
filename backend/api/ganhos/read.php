<?php
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';


$usuario_id = requireAuth();


$stmt = $conexao->prepare(
    "SELECT * FROM ganhos 
     WHERE usuario_id = ? 
     ORDER BY data_ganho DESC"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$ganhos = [];
while ($row = $result->fetch_assoc()) {
    $ganhos[] = $row;
}


echo json_encode([
    "status" => "success",
    "ganhos" => $ganhos
]);
?>
