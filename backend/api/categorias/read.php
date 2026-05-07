<?php
// backend/api/categorias/read.php — Retorna categorias do usuário por tipo
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';


// Autenticação
$usuario_id = requireAuth();


// Receber parâmetro de query string
$tipo = $_GET['tipo'] ?? null;


// Validar parâmetro tipo
if (!in_array($tipo, ['ganho', 'despesa'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipo de categoria inválido.']);
    exit;
}


// Consultar categorias do usuário e globais
$stmt = $conexao->prepare(
    "SELECT id, nome FROM categorias
     WHERE tipo = ? AND (usuario_id IS NULL OR usuario_id = ?)
     ORDER BY nome ASC"
);
$stmt->bind_param("si", $tipo, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = [
        'id'   => (int)$row['id'],
        'nome' => $row['nome'],
    ];
}


// Retornar resposta
echo json_encode([
    'status'     => 'success',
    'categorias' => $categorias,
]);
?>
