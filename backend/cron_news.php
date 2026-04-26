<?php
/**
 * backend/cron_news.php
 * Cron job de atualização de notícias financeiras.
 * Agora chama o processador diretamente (sem HTTP) para evitar timeout do Apache.
 *
 * Como executar:
 *   → Terminal (recomendado): php /Applications/MAMP/htdocs/InvestAi/backend/cron_news.php
 *   → Agendamento:  0 * * * * /Applications/MAMP/bin/php/php8.4.1/bin/php /path/to/cron_news.php
 */

define('CRON_MODE', true);
set_time_limit(0);
@ini_set('max_execution_time', 0);
@error_reporting(0);

$root = dirname(__FILE__);
require_once $root . '/services/NewsAggregator.php';

// ─── Carregar .env ────────────────────────────────────────────────────────────
$envFile = dirname($root) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
        $_ENV[trim($k)] = trim($v);
    }
}

// ─── Log ─────────────────────────────────────────────────────────────────────
$logDir  = $root . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/cron_news.log';

function cronLog(string $msg): void {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

// ─── Cooldown ─────────────────────────────────────────────────────────────────
$lockFile = $logDir . '/cron_news.lock';
if (file_exists($lockFile)) {
    $lastRun = (int)file_get_contents($lockFile);
    if (time() - $lastRun < 120) {
        $minutos = round((time() - $lastRun) / 60, 1);
        cronLog("Skipped — cooldown ativo ({$minutos} min). Aguarde 2 min.");
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'skipped', 'mensagem' => "Cooldown ativo ({$minutos} min)."]);
        }
        exit;
    }
}
file_put_contents($lockFile, time());

// ─── Início ───────────────────────────────────────────────────────────────────
$inicio = microtime(true);
cronLog('=== InvestAI News Cron — Iniciando ===');

// ─── Passo 1: Coletar RSS ────────────────────────────────────────────────────
cronLog('Passo 1/3: Coletando RSS...');
$aggregator = new NewsAggregator();
$noticias   = $aggregator->fetch(15);

cronLog('  → Total coletado: ' . count($noticias) . ' notícias');

if (empty($noticias)) {
    cronLog('AVISO: Nenhuma notícia encontrada. Encerrando.');
    file_put_contents($lockFile, 0);
    exit;
}

// ─── Passo 2: Processar com Gemini AI (direto, sem HTTP) ─────────────────────
cronLog('Passo 2/3: Processando com Gemini AI...');

$geminiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
if (empty($geminiKey)) {
    cronLog('ERRO: GEMINI_API_KEY não configurada no .env');
    exit(1);
}

// Conexão com banco
require_once dirname($root) . '/DataBase/conexao.php';

$categoriasValidas = ['Transporte','Alimentação','Moradia','Lazer','Tecnologia','Saúde','Finanças Gerais'];
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . urlencode($geminiKey);

// ─── Prompt com exemplos detalhados ──────────────────────────────────────────
function montarPromptCron(array $lote): string {
    $noticiasTexto = '';
    foreach ($lote as $i => $n) {
        $idx    = $i + 1;
        $titulo = $n['titulo'] ?? '';
        $resumo = mb_substr($n['resumo'] ?? '', 0, 200);
        $fonte  = $n['fonte']  ?? '';
        $noticiasTexto .= "\n{$idx}. [{$fonte}] {$titulo}\n   Resumo: {$resumo}\n";
    }

    return <<<PROMPT
Você é o Arquiteto Financeiro do InvestAI. Classifique cada notícia na categoria que MELHOR representa seu impacto na vida cotidiana do brasileiro.

CATEGORIAS E EXEMPLOS:
- "Transporte": gasolina, diesel, etanol, pedágio, ônibus, metrô, frete, carros, combustíveis, petróleo, tarifas de transporte
- "Alimentação": alimentos, cesta básica, supermercado, carne, frango, soja, milho, arroz, feijão, restaurante, delivery, inflação de alimentos, agronegócio alimentar
- "Moradia": aluguel, condomínio, IPTU, energia elétrica, conta de luz, água, gás, imóvel, construção civil, financiamento imobiliário, reforma
- "Lazer": turismo, viagem, passagem aérea, entretenimento, streaming, academia, shows, hotéis, parques, férias, esportes de lazer
- "Tecnologia": inteligência artificial, IA, smartphones, computadores, internet, software, apps, criptomoedas, bitcoin, blockchain, startups de tecnologia, data centers
- "Saúde": plano de saúde, medicamentos, farmácias, consultas médicas, hospitais, vigilância sanitária, seguro saúde, dengue econômica
- "Finanças Gerais": juros Selic, Copom, câmbio dólar, bolsa de valores B3, Tesouro Direto, impostos federais, dívida pública, PIB, desemprego, política econômica, banco central, mercado financeiro em geral

REGRA CRÍTICA: NÃO use "Finanças Gerais" como padrão. Só use quando nenhuma outra categoria se encaixar melhor. Petróleo/combustíveis = Transporte. Alimentos/agro = Alimentação. IA/tech = Tecnologia.

NOTÍCIAS:
{$noticiasTexto}

Retorne APENAS JSON puro, sem markdown, sem texto extra, exatamente neste formato:
{"analises":[{"titulo_noticia":"título exato da notícia","categoria":"UMA das 7 categorias","nivel_impacto":"alto|medio|baixo","cenario_hipotetico":"como isso afeta o brasileiro em 3-6 meses","acoes_praticas":["ação 1","ação 2","ação 3"],"sugestao_investimento":"sugestão específica","dica_economia":"dica prática"}]}
PROMPT;
}

// ─── Função: chamar Gemini ────────────────────────────────────────────────────
function chamarGeminiCron(string $prompt, string $apiUrl): ?array {
    $body = json_encode([
        'contents'         => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 8192],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (!$resp || $httpCode !== 200) {
        cronLog("  → Gemini HTTP {$httpCode}: " . mb_substr($resp ?? '', 0, 200));
        return null;
    }

    $data = json_decode($resp, true);
    $raw  = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

    // Limpar markdown
    if (preg_match('/```(?:json)?\s*([\s\S]+?)```/', $raw, $m)) $raw = trim($m[1]);

    $result = json_decode($raw, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $result : null;
}

// ─── Processar em lotes de 12 ────────────────────────────────────────────────
$loteSize      = 12;
$lotes         = array_chunk($noticias, $loteSize);
$todasAnalises = [];
$loteErros     = 0;

foreach ($lotes as $idxLote => $lote) {
    cronLog("  → Lote " . ($idxLote + 1) . "/" . count($lotes) . " (" . count($lote) . " notícias)...");
    $prompt    = montarPromptCron($lote);
    $resultado = chamarGeminiCron($prompt, $apiUrl);

    if ($resultado && !empty($resultado['analises'])) {
        $todasAnalises = array_merge($todasAnalises, $resultado['analises']);
        cronLog("    ✓ " . count($resultado['analises']) . " análises recebidas");
    } else {
        cronLog("    ✗ Falha no lote " . ($idxLote + 1));
        $loteErros++;
    }

    if ($idxLote < count($lotes) - 1) usleep(500000); // 0.5s entre lotes
}

cronLog("  → Total análises da IA: " . count($todasAnalises));

// ─── Fallback: categorização local por palavras-chave ────────────────────────
function categorizarLocal(string $titulo, string $resumo): string {
    $texto = mb_strtolower($titulo . ' ' . $resumo);
    $mapa = [
        'Transporte'   => ['gasolina','diesel','etanol','combustível','pedágio','ônibus','metrô','trem','uber','frete','petróleo','tarifa de transporte','preço do gás','posto de gasolina'],
        'Alimentação'  => ['alimento','cesta básica','supermercado','carne','frango','soja','milho','arroz','feijão','açúcar','leite','café','restaurante','delivery','preço dos alimentos','agronegócio alimentar','feira livre','pão','custo alimentar'],
        'Moradia'      => ['aluguel','condomínio','iptu','energia elétrica','conta de luz','tarifa de água','gás encanado','imóvel','construção civil','financiamento imobiliário','reforma','moradia','habitação'],
        'Lazer'        => ['turismo','viagem','passagem aérea','entretenimento','streaming','academia','show','hotel','parque temático','férias','lazer'],
        'Tecnologia'   => ['inteligência artificial','ia ','a.i.','smartphone','computador','notebook','internet','software','aplicativo','criptomoeda','bitcoin','blockchain','startup','data center','chip','semicondutor','tecnologia'],
        'Saúde'        => ['plano de saúde','medicamento','farmácia','consulta médica','hospital','clínica','vigilância sanitária','seguro saúde','remédio','anvisa','sus ','saúde pública'],
    ];
    foreach ($mapa as $cat => $palavras) {
        foreach ($palavras as $p) {
            if (mb_strpos($texto, $p) !== false) return $cat;
        }
    }
    return 'Finanças Gerais';
}

// ─── Indexar análises por título ──────────────────────────────────────────────
$analiseMap = [];
foreach ($todasAnalises as $a) {
    $key = mb_strtolower(mb_substr($a['titulo_noticia'] ?? '', 0, 60));
    $analiseMap[$key] = $a;
}

// ─── Persistir no banco ───────────────────────────────────────────────────────
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

$inseridos = 0;
$ignorados = 0;

foreach ($noticias as $n) {
    $titulo     = mb_substr($n['titulo'] ?? '', 0, 255);
    $fonte      = $n['fonte']       ?? '';
    $url        = mb_substr($n['link'] ?? '#', 0, 500);
    $resumo     = $n['resumo']      ?? '';
    $corFonte   = $n['cor_fonte']   ?? '#6366f1';
    $iconeFonte = $n['icone_fonte'] ?? 'bi-newspaper';
    $dataPub    = $n['data']        ?? date('Y-m-d H:i:s');

    // Busca exata
    $key     = mb_strtolower(mb_substr($titulo, 0, 60));
    $analise = $analiseMap[$key] ?? null;

    // Busca aproximada
    if (!$analise) {
        foreach ($analiseMap as $k => $a) {
            similar_text($key, $k, $pct);
            if ($pct > 65) { $analise = $a; break; }
        }
    }

    $categoria    = categorizarLocal($titulo, $resumo); // fallback local
    $nivelImpacto = 'medio';
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

    if ($stmt->execute()) $inseridos++;
    else $ignorados++;
}

// ─── Passo 3: Limpeza ─────────────────────────────────────────────────────────
cronLog('Passo 3/3: Limpando notícias com mais de 7 dias...');
$stmtDel = $conexao->prepare("DELETE FROM noticias_financeiras WHERE criado_em < NOW() - INTERVAL 7 DAY");
$stmtDel->execute();
$deletados = $stmtDel->affected_rows;
cronLog("  → {$deletados} notícias removidas.");

// ─── Sumário ──────────────────────────────────────────────────────────────────
$duracao = round(microtime(true) - $inicio, 2);
cronLog("  → Inseridos/Atualizados: {$inseridos} | Ignorados: {$ignorados}");
cronLog("=== Concluído em {$duracao}s ===");

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'    => 'ok',
        'coletadas' => count($noticias),
        'inseridos' => $inseridos,
        'deletados' => $deletados,
        'duracao_s' => $duracao,
    ]);
}
