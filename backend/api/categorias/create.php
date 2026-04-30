<?php
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';

$usuario_id = requireAuth();
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$nome = trim($input['nome'] ?? '');
$tipo = $input['tipo'] ?? '';

if (empty($nome) || !in_array($tipo, ['ganho', 'despesa'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos para categoria.']);
    exit;
}

$stmt = $conexao->prepare("INSERT INTO categorias (usuario_id, nome, tipo) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $usuario_id, $nome, $tipo);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Categoria criada com sucesso!',
        'categoria' => [
            'id' => $stmt->insert_id,
            'nome' => $nome,
            'is_custom' => true
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao criar categoria.']);
}
?>
