<?php
/**
 * backend/includes/ai_handler.php
 * Gerenciador central de IA: suporta Ollama (Local) com Fallback para Gemini API.
 */

function get_gemini_api_key() {
    $key = getenv('GEMINI_API_KEY');
    if ($key) return trim($key);
    $env_path = dirname(dirname(dirname(__FILE__))) . '/.env';
    if (file_exists($env_path)) {
        foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (strpos($line, 'GEMINI_API_KEY=') === 0) {
                return trim(substr($line, strlen('GEMINI_API_KEY=')), " \"'");
            }
        }
    }
    return null;
}

/**
 * Chama a IA disponível (Ollama primeiro, depois Gemini).
 */
function call_ai_service($prompt, $options = []) {
    $temperature = $options['temperature'] ?? 0.7;
    $max_tokens  = $options['max_tokens'] ?? 2000;
    $model_ollama = $options['ollama_model'] ?? 'llama3';

    // 1. Tentar Ollama (Local)
    $ollama_res = call_ollama_local($prompt, $model_ollama, $temperature);
    if ($ollama_res) {
        return [
            'success' => true,
            'source'  => 'ollama',
            'data'    => $ollama_res
        ];
    }

    // 2. Fallback para Gemini
    $gemini_key = get_gemini_api_key();
    if ($gemini_key) {
        $gemini_res = call_gemini_api($prompt, $gemini_key, $temperature, $max_tokens);
        if ($gemini_res) {
            return [
                'success' => true,
                'source'  => 'gemini',
                'data'    => $gemini_res
            ];
        }
    }

    return [
        'success' => false,
        'message' => 'Nenhum serviço de IA disponível (Ollama offline e Gemini sem chave ou cota).'
    ];
}

/**
 * Comunicação com Ollama Local
 */
function call_ollama_local($prompt, $model, $temp) {
    $url = "http://localhost:11434/api/generate";
    $data = [
        "model"   => $model,
        "prompt"  => $prompt,
        "stream"  => false,
        "options" => ["temperature" => $temp]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 120, // Aumentado para 2 minutos para análises profundas
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    // LOG NA RAIZ DO PROJETO (MAMP root)
    $log_msg = "Time: " . date('H:i:s') . " | Code: $http_code | Err: $curl_err | RAW Start: " . substr($response, 0, 80) . "...\n";
    file_put_contents(dirname(dirname(dirname(__FILE__))) . '/ai_debug.log', $log_msg, FILE_APPEND);

    if ($http_code === 200 && $response) {
        $json = json_decode($response, true);
        $ai_text = $json['response'] ?? null;
        
        // Log do texto extraído e limpo para depuração
        $cleaned = clean_ai_json($ai_text);
        file_put_contents(dirname(dirname(dirname(__FILE__))) . '/ai_debug.log', "Time: " . date('H:i:s') . " | CLEANED Start: " . substr($cleaned, 0, 80) . "...\n", FILE_APPEND);
        
        return $ai_text;
    }
    return null;
}

/**
 * Comunicação com Gemini API
 */
function call_gemini_api($prompt, $key, $temp, $tokens) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($key);
    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "temperature"     => $temp,
            "maxOutputTokens" => $tokens,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 35,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $json = json_decode($response, true);
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
    return null;
}

/**
 * Limpa a resposta da IA (remove markdown de JSON e textos extras)
 */
function clean_ai_json($raw) {
    // 1. Remove espaços em branco e caracteres invisíveis no início/fim
    $raw = trim($raw);
    
    // 2. Tenta encontrar bloco de código markdown ```json ... ```
    if (preg_match('/```(?:json)?\s*([\s\S]+?)```/', $raw, $m)) {
        $raw = trim($m[1]);
    } else {
        // 3. Se não houver markdown, tenta encontrar o primeiro { e o último }
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $raw = substr($raw, $start, $end - $start + 1);
        }
    }
    
    // 4. Limpeza profunda: remove caracteres de controle que podem quebrar o JSON
    // (Exceto tabulação, quebra de linha e retorno de carro)
    $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw);
    
    return $raw;
}
