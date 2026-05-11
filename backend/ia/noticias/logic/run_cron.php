<?php
/**
 * backend/ia/noticias/logic/run_cron.php
 * Gatilho web que executa o cron via CLI (sem limite de tempo do Apache).
 * Chamado pelo botão "Atualizar" da interface.
 */

ob_start();
@error_reporting(0);
@ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['usuario_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensagem' => 'Não autorizado.']);
    exit;
}

$backendRoot = dirname(dirname(dirname(__DIR__)));   // backend/
$lockFile    = $backendRoot . '/logs/cron_news.lock';
$logFile     = $backendRoot . '/logs/cron_news.log';
$phpBin      = PHP_BINARY; // Detecta automaticamente o binário PHP (funciona em XAMPP, MAMP, etc.)
$cronFile    = __DIR__ . '/cron_news.php';

if (file_exists($lockFile)) {
    $lastRun = (int)file_get_contents($lockFile);
    if (time() - $lastRun < 120) {
        $minutos = round((time() - $lastRun) / 60, 1);
        ob_end_clean();
        echo json_encode([
            'status'   => 'skipped',
            'mensagem' => "Cooldown ativo (última execução há {$minutos} min). Aguarde 2 minutos.",
        ]);
        exit;
    }
}

// Executa em background de forma cross-platform (Windows usa start /B, Unix usa &)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $cmd = 'start /B "" ' . escapeshellarg($phpBin) . ' ' . escapeshellarg($cronFile) . ' >> ' . escapeshellarg($logFile) . ' 2>&1';
} else {
    $cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($cronFile) . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &';
}
$output = [];
exec($cmd, $output);

ob_end_clean();
echo json_encode([
    'status'   => 'iniciado',
    'mensagem' => 'Cron iniciado em background. Aguarde ~20s e recarregue as notícias.',
]);
