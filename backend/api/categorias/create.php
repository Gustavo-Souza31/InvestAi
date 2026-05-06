<?php
// backend/api/categorias/create.php — Cria nova categoria para o usuário autenticado
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/validators/CategoriasValidator.php';


// Autenticação
$usuario_id = requireAuth();


// Receber dados do body JSON ou FormData
$input      = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$validation = CategoriasValidator::validateCreate($input);


// Validar dados contra regras de negócio
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $validation['errors'][0]]);
    exit;
}

$nome = $validation['data']['nome'];
$tipo = $validation['data']['tipo'];


// Inserir categoria no banco de dados
$stmt = $conexao->prepare("INSERT INTO categorias (usuario_id, nome, tipo) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $usuario_id, $nome, $tipo);


// Executar e verificar inserção
if ($stmt->execute()) {
    echo json_encode([
        'status'    => 'success',
        'message'   => 'Categoria criada com sucesso!',
        'categoria' => ['id' => $stmt->insert_id, 'nome' => $nome, 'is_custom' => true],
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao criar categoria.']);
}
?>
