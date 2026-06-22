<?php
// backend/api/chat/mensagem.php — Processa mensagem do chat e retorna resposta da IA
header('Content-Type: application/json');

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/backend/database/conexao.php';
require_once $root . '/backend/includes/auth_middleware.php';
require_once $root . '/backend/includes/Logger.php';
require_once $root . '/backend/ia/chat/ChatAgent.php';


$usuario_id    = requireAuth();
$usuario_email = $_SESSION['usuario_email'] ?? null;


// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST obrigatório.']);
    exit;
}


// Receber dados do body JSON
$body     = json_decode(file_get_contents('php://input'), true);
$mensagem = trim($body['mensagem'] ?? '');

if ($mensagem === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mensagem não pode ser vazia.']);
    exit;
}

if (mb_strlen($mensagem) > 500) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mensagem muito longa (máx. 500 caracteres).']);
    exit;
}


// Parâmetros de mês/ano (padrão: mês atual)
$mes = (int) ($body['mes'] ?? date('m'));
$ano = (int) ($body['ano'] ?? date('Y'));

// Histórico de conversa (últimas mensagens da sessão)
$historico_raw = $body['historico'] ?? [];
$historico = [];
foreach (array_slice((array) $historico_raw, -20) as $item) {
    $role  = (string) ($item['role']  ?? '');
    $texto = trim((string) ($item['texto'] ?? ''));
    if (($role === 'usuario' || $role === 'assistente') && $texto !== '') {
        $historico[] = ['role' => $role, 'texto' => $texto];
    }
}


// Processar mensagem com o agente
try {
    $agent     = new ChatAgent($conexao);
    $resultado = $agent->processar($mensagem, $usuario_id, $mes, $ano, $historico);

    Logger::log('INFO', 'CHAT_MESSAGE', ['tamanho' => mb_strlen($mensagem), 'acao' => $resultado['acao']], 'sucesso', $usuario_id, $usuario_email);

    echo json_encode([
        'status'              => 'success',
        'resposta'            => $resultado['resposta'],
        'acao'                => $resultado['acao'],
        'precisa_confirmacao' => $resultado['precisa_confirmacao'] ?? false,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    Logger::log('ERROR', 'CHAT_ERROR', ['erro' => $e->getMessage()], 'falha', $usuario_id, $usuario_email);
    error_log("Erro em chat/mensagem.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao processar a mensagem.']);
}
?>
