<?php
/**
 * backend/api/noticias/ai_news_processor.php
 * Recebe notícias brutas do RSS, processa via Gemini AI
 * e persiste os resultados na tabela noticias_financeiras.
 */

// Buffer ANTES de tudo — captura qualquer warning PHP que pudesse vazar
ob_start();
@error_reporting(0);
@ini_set('display_errors', 0);
set_time_limit(0);           // Sem timeout — processamento em lotes pode demorar
@ini_set('max_execution_time', 0);
header('Content-Type: application/json; charset=utf-8');

// ─── Autenticação interna por token ou IP ────────────────────────────────────
// Aceita chamadas de localhost (cron) ou com header X-Cron-Token
$cronToken = 'investai_cron_2025';
$fromLocal  = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$tokenOk    = (($_SERVER['HTTP_X_CRON_TOKEN'] ?? '') === $cronToken);

if (!$fromLocal && !$tokenOk) {
    http_response_code(403);
    
    ob_end_clean(); echo json_encode(['status' => 'error', 'mensagem' => 'Acesso negado.']);
    exit;
}

// ─── Corpo da requisição ─────────────────────────────────────────────────────
$body     = json_decode(file_get_contents('php://input'), true);
$noticias = $body['noticias'] ?? [];

if (empty($noticias)) {
    http_response_code(400);
    
    ob_end_clean(); echo json_encode(['status' => 'error', 'mensagem' => 'Nenhuma notícia recebida.']);
    exit;
}

// ─── Conexão com banco ───────────────────────────────────────────────────────
$root = dirname(dirname(dirname(dirname(__FILE__)))); // backend/api/noticias/ -> raiz do projeto
require_once $root . '/DataBase/conexao.php';

// ─── Chave Gemini ────────────────────────────────────────────────────────────
function getGeminiKey(string $root): ?string
{
    $key = getenv('GEMINI_API_KEY');
    if ($key) return trim($key);

    $envPath = $root . '/.env';
    if (file_exists($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (strpos($line, 'GEMINI_API_KEY=') === 0) {
                return trim(substr($line, strlen('GEMINI_API_KEY=')), " \"'");
            }
        }
    }
    return null;
}

$geminiKey = getGeminiKey($root);
if (!$geminiKey) {
    
    ob_end_clean(); echo json_encode(['status' => 'sem_chave', 'mensagem' => 'GEMINI_API_KEY não configurada.']);
    exit;
}

// ─── Função: montar prompt para um lote ──────────────────────────────────────
function montarPrompt(array $lote): string
{
    $noticiasTexto = '';
    foreach ($lote as $i => $n) {
        $idx    = $i + 1;
        $titulo = $n['titulo'] ?? '';
        $resumo = mb_substr($n['resumo'] ?? '', 0, 180);
        $fonte  = $n['fonte']  ?? '';
        $noticiasTexto .= "\n{$idx}. [{$fonte}] {$titulo}\n   Resumo: {$resumo}\n";
    }

    return <<<PROMPT
Você é o Arquiteto Financeiro do InvestAI. Classifique cada notícia na categoria que MELHOR representa o impacto na vida cotidiana do brasileiro.

CATEGORIAS E EXEMPLOS DE USO:
- "Transporte": gasolina, diesel, etanol, pedágio, tarifa de ônibus/metrô, frete, preço de carros, combustíveis, petróleo
- "Alimentação": alimentos, cesta básica, supermercado, preço de carne/frango/soja/milho, feira, restaurante, delivery, inflação de alimentos
- "Moradia": aluguel, condomínio, IPTU, energia elétrica, água, gás, reforma, imóvel, construção civil, financiamento imóvel
- "Lazer": turismo, viagem, passagem aérea, entretenimento, streaming, academia, shows, hotéis, parques, férias
- "Tecnologia": IA, smartphones, computadores, internet, software, apps, criptomoedas, blockchain, startups de tech
- "Saúde": plano de saúde, medicamentos, farmácias, consultas, hospitais, vigilância sanitária, seguro saúde
- "Finanças Gerais": juros Selic, Copom, câmbio, bolsa de valores, Tesouro Direto, impostos, dívida pública, PIB, emprego, política econômica, banco central, mercado financeiro

INSTRUÇÃO CRÍTICA: NÃO coloque tudo em "Finanças Gerais". Use a categoria mais específica. Se a notícia fala de petróleo/gasolina → "Transporte". Se fala de alimentos → "Alimentação". Só use "Finanças Gerais" se for sobre política monetária, bolsa, câmbio, ou algo sem encaixe melhor.

NOTÍCIAS:
{$noticiasTexto}

Retorne APENAS JSON puro (sem markdown, sem ```, sem texto extra):
{"analises":[{"titulo_noticia":"título exato","categoria":"UMA das 7 categorias","nivel_impacto":"alto|medio|baixo","cenario_hipotetico":"1-2 frases sobre impacto financeiro","acoes_praticas":["ação1","ação2","ação3"],"sugestao_investimento":"1 sugestão","dica_economia":"1 dica"}]}

REGRAS: nivel_impacto = exatamente "alto", "medio" ou "baixo". acoes_praticas = exatamente 3 strings. Responda em português do Brasil.
PROMPT;
}

// ─── Função: chamar Gemini API ────────────────────────────────────────────────
function chamarGemini(string $prompt, string $apiUrl): ?array
{
    $reqBody = json_encode([
        'contents'         => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 4000],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $reqBody,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 55,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (!$response || $httpCode !== 200) return null;

    $geminiData = json_decode($response, true);
    $raw = trim($geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '');

    // Limpar markdown se presente
    if (preg_match('/```(?:json)?\s*([\s\S]+?)```/', $raw, $m)) {
        $raw = trim($m[1]);
    }

    $resultado = json_decode($raw, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $resultado : null;
}

// ─── Processar em lotes de 10 ─────────────────────────────────────────────────
$apiUrl         = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . urlencode($geminiKey);
$loteSize       = 10;
$lotes          = array_chunk($noticias, $loteSize);
$todasAnalises  = [];

foreach ($lotes as $lote) {
    $prompt    = montarPrompt($lote);
    $resultado = chamarGemini($prompt, $apiUrl);
    if ($resultado && !empty($resultado['analises'])) {
        $todasAnalises = array_merge($todasAnalises, $resultado['analises']);
    }
    // Pequena pausa entre lotes para não sobrecarregar a API
    if (count($lotes) > 1) usleep(300000); // 0.3s
}

// ─── Indexar análises por título ──────────────────────────────────────────────
$categoriasValidas = ['Transporte','Alimentação','Moradia','Lazer','Tecnologia','Saúde','Finanças Gerais'];
$analiseMap = [];
foreach ($todasAnalises as $a) {
    $tituloNorm = mb_strtolower(mb_substr($a['titulo_noticia'] ?? '', 0, 60));
    $analiseMap[$tituloNorm] = $a;
}

// ─── Persistir no banco ───────────────────────────────────────────────────────
$inseridos = 0;
$ignorados = 0;

$sql = "INSERT INTO noticias_financeiras
            (titulo, fonte, url, resumo, categoria, data_publicacao,
             nivel_impacto, cenario_hipotetico, acoes_praticas,
             sugestao_investimento, dica_economia, cor_fonte, icone_fonte, processado_ia)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            categoria             = VALUES(categoria),
            nivel_impacto         = VALUES(nivel_impacto),
            cenario_hipotetico    = VALUES(cenario_hipotetico),
            acoes_praticas        = VALUES(acoes_praticas),
            sugestao_investimento = VALUES(sugestao_investimento),
            dica_economia         = VALUES(dica_economia),
            processado_ia         = 1,
            atualizado_em         = CURRENT_TIMESTAMP";

$stmt = $conexao->prepare($sql);

foreach ($noticias as $n) {
    $titulo     = mb_substr($n['titulo'] ?? '', 0, 255);
    $fonte      = $n['fonte']       ?? '';
    $url        = mb_substr($n['link'] ?? '#', 0, 500);
    $resumo     = $n['resumo']      ?? '';
    $corFonte   = $n['cor_fonte']   ?? '#6366f1';
    $iconeFonte = $n['icone_fonte'] ?? 'bi-newspaper';
    $dataPub    = $n['data']        ?? date('Y-m-d H:i:s');

    // Encontrar análise correspondente
    $tituloNorm = mb_strtolower(mb_substr($titulo, 0, 60));
    $analise    = $analiseMap[$tituloNorm] ?? null;

    // Busca aproximada se não achou exato
    if (!$analise) {
        foreach ($analiseMap as $k => $a) {
            similar_text($tituloNorm, $k, $pct);
            if ($pct > 68) { $analise = $a; break; }
        }
    }

    $categoria    = 'Finanças Gerais';
    $nivelImpacto = 'baixo';
    $cenario      = null;
    $acoes        = null;
    $sugestao     = null;
    $dica         = null;

    if ($analise) {
        $cat = $analise['categoria'] ?? '';
        $categoria    = in_array($cat, $categoriasValidas) ? $cat : 'Finanças Gerais';
        $nivelImpacto = in_array($analise['nivel_impacto'] ?? '', ['alto','medio','baixo'])
                        ? $analise['nivel_impacto'] : 'baixo';
        $cenario  = mb_substr($analise['cenario_hipotetico'] ?? '', 0, 1000);
        $acoes    = json_encode($analise['acoes_praticas'] ?? [], JSON_UNESCAPED_UNICODE);
        $sugestao = mb_substr($analise['sugestao_investimento'] ?? '', 0, 500);
        $dica     = mb_substr($analise['dica_economia'] ?? '', 0, 500);
    }

    $stmt->bind_param(
        'sssssssssssss',
        $titulo, $fonte, $url, $resumo, $categoria, $dataPub,
        $nivelImpacto, $cenario, $acoes, $sugestao, $dica,
        $corFonte, $iconeFonte
    );

    if ($stmt->execute()) {
        $inseridos++;
    } else {
        $ignorados++;
    }
}

ob_end_clean();
echo json_encode([
    'status'    => 'ok',
    'inseridos' => $inseridos,
    'ignorados' => $ignorados,
    'total_ia'  => count($todasAnalises),
    'lotes'     => count($lotes),
], JSON_UNESCAPED_UNICODE);
