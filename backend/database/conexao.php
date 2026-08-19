<?php
// backend/database/conexao.php — Conexão centralizada ao banco
// Carrega configurações de .env

require_once __DIR__ . '/../config/ConfigHelper.php';
ConfigHelper::load();

// Em produção (ex: Railway), o plugin de MySQL injeta MYSQL* em vez de DB_*.
// Usamos DB_* como principal e caímos para as variáveis do Railway se ausentes.
$servername = ConfigHelper::get('DB_HOST', ConfigHelper::get('MYSQLHOST', '127.0.0.1'));
$usuario_db = ConfigHelper::get('DB_USER', ConfigHelper::get('MYSQLUSER', 'root'));
$senha_db = ConfigHelper::get('DB_PASS', ConfigHelper::get('MYSQLPASSWORD', ''));
$banco = ConfigHelper::get('DB_NAME', ConfigHelper::get('MYSQLDATABASE', 'investai'));
$porta = ConfigHelper::get('DB_PORT', ConfigHelper::get('MYSQLPORT', 3306));

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// SSL só é necessário para bancos em nuvem que o exigem (ex: TiDB Serverless).
// A maioria dos provedores (XAMPP local, MySQL do Railway) NÃO precisa/suporta SSL,
// então isso é controlado explicitamente via .env em vez de inferido pelo host.
$usarSsl = filter_var(ConfigHelper::get('DB_SSL', 'false'), FILTER_VALIDATE_BOOLEAN);

try {
    $conexao = mysqli_init();

    if ($usarSsl) {
        $conexao->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
            $conexao->ssl_set(NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);
        }
        $conexao->real_connect($servername, $usuario_db, $senha_db, $banco, $porta, NULL, MYSQLI_CLIENT_SSL);
    } else {
        $conexao->real_connect($servername, $usuario_db, $senha_db, $banco, $porta);
    }

    $conexao->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log('[conexao.php] Erro ao conectar ao banco: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Não foi possível conectar ao banco de dados. Tente novamente mais tarde."]);
    exit;
}
?>
