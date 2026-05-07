<?php
// backend/includes/Logger.php — Sistema centralizado de logs de auditoria

require_once __DIR__ . '/../config/ConfigHelper.php';

class Logger
{
    private static function getConnection(): ?mysqli
    {
        ConfigHelper::load();
        $conn = @new mysqli(
            ConfigHelper::get('DB_HOST', '127.0.0.1'),
            ConfigHelper::get('DB_USER', 'root'),
            ConfigHelper::get('DB_PASS', ''),
            ConfigHelper::get('DB_NAME', 'investai'),
            (int) ConfigHelper::get('DB_PORT', 3306)
        );
        if ($conn->connect_error) {
            return null;
        }
        $conn->set_charset('utf8mb4');
        return $conn;
    }

    private static function getIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    /**
     * Registra um evento de auditoria no banco de dados.
     *
     * @param string      $nivel         INFO | WARN | ERROR | DEBUG
     * @param string      $acao          Identificador da ação (ex: USER_LOGIN)
     * @param array       $detalhes      Dados adicionais relevantes
     * @param string      $status        sucesso | falha
     * @param int|null    $usuario_id    ID do usuário responsável
     * @param string|null $usuario_email E-mail do usuário responsável
     */
    public static function log(
        string  $nivel,
        string  $acao,
        array   $detalhes      = [],
        string  $status        = 'sucesso',
        ?int    $usuario_id    = null,
        ?string $usuario_email = null
    ): void {
        try {
            $conn = self::getConnection();
            if (!$conn) {
                return;
            }

            $ip            = self::getIp();
            $detalhes_json = !empty($detalhes)
                ? json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;

            // usuario_id é INT NULL — bind como string 's' com cast explícito evita
            // problemas de tipo nulo em PHP 7.4 com bind_param 'i'
            $uid_str = $usuario_id !== null ? (string) $usuario_id : null;

            $stmt = $conn->prepare(
                "INSERT INTO logs (nivel, acao, detalhes, status, usuario_id, usuario_email, ip)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            // tipos: s s s s s s s  (usuario_id inserido como string numérica; MySQL converte)
            $stmt->bind_param(
                'sssssss',
                $nivel,
                $acao,
                $detalhes_json,
                $status,
                $uid_str,
                $usuario_email,
                $ip
            );
            $stmt->execute();
            $stmt->close();
            $conn->close();
        } catch (Throwable $e) {
            // Falha silenciosa — nunca interromper a requisição principal por causa de log
        }
    }
}
?>
