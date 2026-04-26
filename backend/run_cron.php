<?php
/**
 * backend/run_cron.php
 * Gatilho web que executa o cron via CLI (sem limite de tempo do Apache).
 * Chamado pelo botão "Atualizar" da interface.
 */

ob_start();
@error_reporting(0);
@ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// Autenticação de sessão
session_start();
if (!isset($_SESSION['usuario_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensagem' => 'Não autorizado.']);
    exit;
}

$lockFile = __DIR__ . '/logs/cron_news.lock';
$logFile  = __DIR__ . '/logs/cron_news.log';
$phpBin   = '/Applications/MAMP/bin/php/php8.4.1/bin/php';
$cronFile = __DIR__ . '/cron_news.php';

// Verificar cooldown
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

// Executar cron em background via CLI (sem timeout do Apache)
$cmd    = escapeshellarg($phpBin) . ' ' . escapeshellarg($cronFile) . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &';
$output = [];
exec($cmd, $output);

ob_end_clean();
echo json_encode([
    'status'   => 'iniciado',
    'mensagem' => 'Cron iniciado em background. Aguarde ~20s e recarregue as notícias.',
]);
