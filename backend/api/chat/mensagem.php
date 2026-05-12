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


// Carregar chave Gemini do .env
$env_file = $root . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
        $_ENV[trim($k)] = trim($v);
    }
}

$gemini_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');


if (!$gemini_key) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Serviço de IA não configurado.']);
    exit;
}

// Processar mensagem com o agente
try {
    $agent     = new ChatAgent($conexao, $gemini_key);
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
