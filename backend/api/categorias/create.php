<?php
// backend/api/categorias/create.php — Cria nova categoria para o usuário autenticado
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/validators/CategoriasValidator.php';


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Receber dados do body JSON ou FormData
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;


// Validar dados contra regras de negócio
$validation = CategoriasValidator::validateCreate($input);
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
    $novo_id = $stmt->insert_id;
    Logger::log('INFO', 'CATEGORIA_CREATED', ['id' => $novo_id, 'nome' => $nome, 'tipo' => $tipo], 'sucesso', $usuario_id, $usuario_email);
    echo json_encode([
        'status'    => 'success',
        'message'   => 'Categoria criada com sucesso!',
        'categoria' => ['id' => $novo_id, 'nome' => $nome, 'is_custom' => true],
    ]);
} else {
    Logger::log('ERROR', 'CATEGORIA_CREATED', ['nome' => $nome], 'falha', $usuario_id, $usuario_email);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao criar categoria.']);
}
?>
