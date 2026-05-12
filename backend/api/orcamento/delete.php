<?php
// backend/api/orcamento/delete.php — Deleta o orçamento de uma categoria no mês/ano
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}


// Receber dados do body JSON
$body         = json_decode(file_get_contents('php://input'), true);
$categoria_id = intval($body['categoria_id'] ?? 0);
$mes          = intval($body['mes']          ?? date('n'));
$ano          = intval($body['ano']          ?? date('Y'));


// Validar categoria
if ($categoria_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Categoria obrigatória.']);
    exit;
}


// Deletar orçamento do banco de dados
$stmt = $conexao->prepare(
    "DELETE FROM orcamento_categorias WHERE usuario_id = ? AND categoria_id = ? AND mes = ? AND ano = ?"
);
$stmt->bind_param('iiii', $usuario_id, $categoria_id, $mes, $ano);


// Executar e verificar deleção
if ($stmt->execute()) {
    Logger::log('INFO', 'ORCAMENTO_DELETED', ['categoria_id' => $categoria_id, 'mes' => $mes, 'ano' => $ano], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'success', 'message' => 'Orçamento deletado com sucesso!']);
} else {
    Logger::log('ERROR', 'ORCAMENTO_DELETED', ['categoria_id' => $categoria_id], 'falha', $usuario_id, $usuario_email);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao deletar orçamento.']);
}
?>
