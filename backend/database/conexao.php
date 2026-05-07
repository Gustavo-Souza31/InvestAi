<?php
// backend/database/conexao.php — Conexão centralizada ao banco
// Carrega configurações de .env

require_once __DIR__ . '/../config/ConfigHelper.php';
ConfigHelper::load();

$servername = ConfigHelper::get('DB_HOST', '127.0.0.1');
$usuario_db = ConfigHelper::get('DB_USER', 'root');
$senha_db = ConfigHelper::get('DB_PASS', '');
$banco = ConfigHelper::get('DB_NAME', 'investai');
$porta = ConfigHelper::get('DB_PORT', 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexao = mysqli_init();
    
    // TiDB Serverless e outros bancos em nuvem exigem conexão segura (SSL)
    if ($servername !== '127.0.0.1' && $servername !== 'localhost') {
        $conexao->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
            $conexao->ssl_set(NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);
        }
        // Tenta conectar, permitindo SSL caso o banco exija
        $conexao->real_connect($servername, $usuario_db, $senha_db, $banco, $porta, NULL, MYSQLI_CLIENT_SSL);
    } else {
        // Conexão local XAMPP (sem SSL)
        $conexao->real_connect($servername, $usuario_db, $senha_db, $banco, $porta);
    }
    
    $conexao->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro no BD: " . $e->getMessage()]);
    exit;
}
?>
