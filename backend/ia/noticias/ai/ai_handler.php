<?php
/**
 * backend/ia/noticias/ai/ai_handler.php
 * Gerenciador central de IA com Ollama local.
 */

function get_ollama_config(array $options = []): array {
    return [
        'url' => rtrim(getenv('OLLAMA_URL') ?: 'http://localhost:11434', '/'),
        'model' => $options['ollama_model'] ?? getenv('OLLAMA_MODEL') ?? 'llama3.1:latest',
        'timeout' => (int) ($options['timeout'] ?? getenv('OLLAMA_TIMEOUT') ?? 120),
        'temperature' => (float) ($options['temperature'] ?? 0.7),
        'max_tokens' => (int) ($options['max_tokens'] ?? 2000),
    ];
}

/**
 * Chama a IA local via Ollama.
 */
function call_ai_service($prompt, $options = []) {
    $config = get_ollama_config($options);

    $payload = [
        'model' => $config['model'],
        'stream' => false,
        'messages' => [
            ['role' => 'system', 'content' => 'Você é um assistente financeiro que responde em português do Brasil e deve seguir exatamente o formato solicitado.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'options' => [
            'temperature' => $config['temperature'],
            'num_predict' => $config['max_tokens'],
        ],
    ];

    $ch = curl_init($config['url'] . '/api/chat');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $json = json_decode($response, true);
        $text = trim($json['message']['content'] ?? '');
        if ($text === '' && isset($json['response'])) {
            $text = trim((string) $json['response']);
        }
        if ($text !== '') {
            return ['success' => true, 'source' => 'ollama', 'data' => $text];
        }
    }

    return [
        'success' => false,
        'message' => 'Serviço de IA local indisponível.'
    ];
}

/**
 * Limpa a resposta da IA (remove markdown de JSON e textos extras)
 */
function clean_ai_json($raw) {
    $raw = trim($raw);

    if (preg_match('/```(?:json)?\s*([\s\S]+?)```/', $raw, $m)) {
        $raw = trim($m[1]);
    } else {
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $raw = substr($raw, $start, $end - $start + 1);
        }
    }
    // 4. Limpeza profunda: remove caracteres de controle que podem quebrar o JSON
    $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw);

    // 5. REPARO BÁSICO: Corrigir falta de aspas em valores comuns (ex: nivel_impacto: Alto)
    // Procura por : seguido de uma palavra sem aspas antes de , ou }
    $raw = preg_replace('/:\s*(Alto|Medio|Baixo|alto|medio|baixo)\s*([,}])/i', ': "$1"$2', $raw);

    // 6. REPARO BÁSICO: Remover vírgulas pendentes (trailing commas)
    $raw = preg_replace('/,\s*([}\]])/', '$1', $raw);
    return $raw;
}
