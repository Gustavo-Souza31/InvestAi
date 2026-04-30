<?php
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';

$usuario_id = requireAuth();
$tipo = $_GET['tipo'] ?? null;

if (!in_array($tipo, ['ganho', 'despesa'])) {
    echo json_encode(['status' => 'error', 'message' => 'Tipo de categoria inválido.']);
    exit;
}

// Ordem: globais primeiro, personalizadas depois, depois por nome
$stmt = $conexao->prepare("SELECT id, nome, usuario_id FROM categorias WHERE tipo = ? AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY usuario_id IS NOT NULL, nome ASC");
$stmt->bind_param("si", $tipo, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = [
        'id' => (int)$row['id'],
        'nome' => $row['nome'],
        'is_custom' => $row['usuario_id'] !== null
    ];
}

echo json_encode([
    'status' => 'success',
    'categorias' => $categorias
]);
?>
