<?php
// backend/includes/Mailer.php — Cliente SMTP mínimo (sem dependências externas)
// Configuração via .env: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM, SMTP_FROM_NAME, SMTP_SECURE (tls|ssl|none)

require_once __DIR__ . '/../config/ConfigHelper.php';

class Mailer
{
    public static function send(string $to, string $subject, string $bodyHtml, string $bodyText = ''): bool
    {
        ConfigHelper::load();

        $host     = ConfigHelper::get('SMTP_HOST', '');
        $port     = (int) ConfigHelper::get('SMTP_PORT', 587);
        $user     = ConfigHelper::get('SMTP_USER', '');
        $pass     = ConfigHelper::get('SMTP_PASS', '');
        $from     = ConfigHelper::get('SMTP_FROM', $user);
        $fromName = ConfigHelper::get('SMTP_FROM_NAME', 'InvestAI');
        $secure   = strtolower(ConfigHelper::get('SMTP_SECURE', 'tls'));

        if ($host === '' || $user === '' || $pass === '') {
            error_log('[Mailer] SMTP não configurado — e-mail não enviado para ' . $to);
            return false;
        }

        $transport = $secure === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]])
        );

        if (!$socket) {
            error_log("[Mailer] Falha ao conectar em {$host}:{$port} — $errstr ($errno)");
            return false;
        }

        stream_set_timeout($socket, 15);

        $readResponse = function ($socket) {
            $response = '';
            while (($line = fgets($socket, 515)) !== false) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $response;
        };

        $expect = function ($socket, array $codes) use ($readResponse) {
            $response = $readResponse($socket);
            $code = (int) substr($response, 0, 3);
            return in_array($code, $codes, true);
        };

        $fail = function (string $motivo) use ($socket): bool {
            error_log('[Mailer] ' . $motivo);
            fclose($socket);
            return false;
        };

        if (!$expect($socket, [220])) return $fail('Servidor SMTP não respondeu com 220 no handshake.');

        $ehloHost = parse_url(ConfigHelper::get('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
        fwrite($socket, "EHLO {$ehloHost}\r\n");
        if (!$expect($socket, [250])) return $fail('EHLO rejeitado.');

        if ($secure === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            if (!$expect($socket, [220])) return $fail('STARTTLS rejeitado.');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return $fail('Falha ao negociar TLS.');
            }
            fwrite($socket, "EHLO {$ehloHost}\r\n");
            if (!$expect($socket, [250])) return $fail('EHLO pós-TLS rejeitado.');
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        if (!$expect($socket, [334])) return $fail('AUTH LOGIN rejeitado.');
        fwrite($socket, base64_encode($user) . "\r\n");
        if (!$expect($socket, [334])) return $fail('Usuário SMTP rejeitado.');
        fwrite($socket, base64_encode($pass) . "\r\n");
        if (!$expect($socket, [235])) return $fail('Autenticação SMTP falhou (usuário/senha).');

        fwrite($socket, "MAIL FROM:<{$from}>\r\n");
        if (!$expect($socket, [250])) return $fail('MAIL FROM rejeitado.');
        fwrite($socket, "RCPT TO:<{$to}>\r\n");
        if (!$expect($socket, [250, 251])) return $fail('RCPT TO rejeitado.');
        fwrite($socket, "DATA\r\n");
        if (!$expect($socket, [354])) return $fail('DATA rejeitado.');

        $boundary = 'inv_' . bin2hex(random_bytes(8));
        $headers = [
            'From: ' . self::encodeHeader($fromName) . " <{$from}>",
            "To: <{$to}>",
            'Subject: ' . self::encodeHeader($subject),
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            'Date: ' . date('r'),
        ];

        $textPart = $bodyText !== '' ? $bodyText : strip_tags($bodyHtml);
        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$textPart}\r\n"
              . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$bodyHtml}\r\n"
              . "--{$boundary}--\r\n";

        $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        // Dot-stuffing: linhas que começam com "." precisam de "." duplicado (RFC 5321)
        $data = preg_replace('/\r\n\./', "\r\n..", $data);

        fwrite($socket, $data . "\r\n.\r\n");
        if (!$expect($socket, [250])) return $fail('Servidor recusou a mensagem no fim do DATA.');

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
