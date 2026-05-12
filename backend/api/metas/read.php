<?php
// backend/api/metas/read.php — Retorna metas do usuário
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';


$usuario_id = requireAuth();


$meta_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($meta_id) {
    $stmt = $conexao->prepare("SELECT * FROM metas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $meta_id, $usuario_id);
    $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc();

    if (!$meta) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Meta não encontrada.']);
        exit;
    }
    echo json_encode(['status' => 'success', 'meta' => $meta]);
    exit;
}

$stmt = $conexao->prepare("SELECT * FROM metas WHERE usuario_id = ? AND ativo = 1 ORDER BY prazo IS NULL, prazo ASC, criado_em DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$metas = [];
while ($row = $result->fetch_assoc()) {
    $metas[] = $row;
}

echo json_encode(['status' => 'success', 'metas' => $metas]);
?>
