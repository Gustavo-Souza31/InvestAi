<?php
/**
 * Config.php - Centraliza todas as constantes de configuração do projeto
 * 
 * Ponto único de verdade para:
 * - APIs (Gemini, Ollama)
 * - Modelos e timeouts
 * - Limites financeiros e alertas
 * - Paths de logs
 * 
 * Carrega automaticamente .env ao ser incluído.
 */

require_once __DIR__ . '/ConfigHelper.php';
ConfigHelper::load();

class Config
{
    // ==================== DATABASE ====================
    const DB_HOST = null;      // Lê de .env
    const DB_PORT = null;      // Lê de .env
    const DB_USER = null;      // Lê de .env
    const DB_PASS = null;      // Lê de .env
    const DB_NAME = null;      // Lê de .env

    // ==================== GEMINI API ====================
    const GEMINI_API_KEY = null;                                                  // Lê de .env
    const GEMINI_MODEL = 'gemini-1.5-flash';                                     // Modelo padrão
    const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models';
    const GEMINI_TIMEOUT = 35;                                                    // Segundos
    const GEMINI_MAX_RETRIES = 2;

    // ==================== OLLAMA (Local AI) ====================
    const OLLAMA_URL = 'http://localhost:11434';
    const OLLAMA_TIMEOUT = 120;                                                   // Segundos (para respostas longas)
    const OLLAMA_MODEL = 'llama2';                                                // Fallback model
    
    // ==================== FINANCIAL LIMITS & ALERTS ====================
    const BUDGET_ALERT_THRESHOLD = 80;                                            // % do orçamento para alertar
    const OVERSPEND_PRIORITY_THRESHOLD_HIGH = 150;                               // % - Prioridade ALTA
    const OVERSPEND_PRIORITY_THRESHOLD_MEDIUM = 100;                             // % - Prioridade MÉDIA

    // Default spending limits por categoria (% da renda)
    const DEFAULT_CATEGORY_LIMITS = [
        'Alimentação'       => 20,
        'Transporte'        => 15,
        'Saúde'             => 8,
        'Educação'          => 10,
        'Entretenimento'    => 5,
        'Utilidades'        => 15,
        'Vestuário'         => 8,
        'Outros'            => 19,
    ];

    // ==================== LOGGING ====================
    const LOG_PATH = 'logs';
    const LOG_AI_DEBUG = 'logs/ai_debug.log';

    // ==================== API RESPONSES ====================
    const RESPONSE_STATUS_SUCCESS = 'success';
    const RESPONSE_STATUS_ERROR = 'error';

    // ==================== HTTP CODES ====================
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_CONFLICT = 409;
    const HTTP_INTERNAL_ERROR = 500;

    /**
     * Obtém valor de .env com fallback para constante da classe
     */
    public static function get($key, $default = null)
    {
        return ConfigHelper::get($key, $default);
    }
}
?>
