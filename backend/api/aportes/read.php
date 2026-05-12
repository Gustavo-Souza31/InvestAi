<?php
// backend/api/aportes/read.php — Lista aportes de uma meta do usuário autenticado
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';

$usuario_id = requireAuth();


// Receber parâmetros de query string
$meta_id = isset($_GET['meta_id']) ? intval($_GET['meta_id']) : null;

if (!$meta_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID da meta é obrigatório.']);
    exit;
}


// Confirma que a meta pertence ao usuário
$stmtMeta = $conexao->prepare("SELECT id FROM metas WHERE id = ? AND usuario_id = ? AND ativo = 1");
$stmtMeta->bind_param("ii", $meta_id, $usuario_id);
$stmtMeta->execute();
if ($stmtMeta->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Meta não encontrada.']);
    exit;
}

$stmt = $conexao->prepare(
    "SELECT id, valor, data_aporte, criado_em
     FROM aportes
     WHERE meta_id = ? AND usuario_id = ?
     ORDER BY data_aporte DESC, criado_em DESC"
);
$stmt->bind_param("ii", $meta_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$aportes = [];
while ($row = $result->fetch_assoc()) {
    $aportes[] = $row;
}

echo json_encode(['status' => 'success', 'aportes' => $aportes]);
?>
