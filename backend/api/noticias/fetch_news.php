<?php
/**
 * backend/api/noticias/fetch_news.php
 * Busca notícias financeiras diretamente dos feeds RSS (sem depender de Python).
 */

// Garante que nenhum warning/notice apareça antes do JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autorizado."]);
    exit;
}

// ─── Configurações dos Feeds ───────────────────────────────────────────────
$feeds = [
    [
        "fonte"   => "G1 Economia",
        "url"     => "https://g1.globo.com/rss/g1/economia/",
        "cor"     => "#ef4444",
        "icone"   => "bi-globe2",
    ],
    [
        "fonte"   => "Valor Econômico",
        "url"     => "https://valor.globo.com/financas/rss",
        "cor"     => "#6366f1",
        "icone"   => "bi-bar-chart-line",
    ],
    [
        "fonte"   => "InfoMoney",
        "url"     => "https://www.infomoney.com.br/feed/",
        "cor"     => "#06b6d4",
        "icone"   => "bi-currency-dollar",
    ],
];

// ─── Palavras que OBRIGATORIAMENTE devem estar presentes (ao menos 1) ─────
$palavras_economicas = [
    // Indicadores e política monetária
    "juros","selic","inflação","ipca","igpm","inpc","pib","banco central","copom",
    "taxa básica","meta de inflação","spread","juro real",
    // Câmbio e mercados
    "dólar","câmbio","euro","libra","iene","bolsa","ibovespa","ações","b3",
    "mercado financeiro","mercado de capitais","índice","dow jones","nasdaq","s&p",
    // Investimentos e renda fixa
    "investimento","cdi","tesouro direto","renda fixa","renda variável","fundo",
    "fii","fundo imobiliário","debenture","cri","cra","lci","lca","poupança",
    "previdência","pgbl","vgbl","criptomoeda","bitcoin","ethereum",
    // Finanças pessoais e crédito
    "crédito","consignado","financiamento","empréstimo","endividamento","inadimplência",
    "salário mínimo","salário","renda","benefício","fgts","inss","seguro desemprego",
    // Setor real e trabalho
    "emprego","desemprego","desocupação","taxa de desocupação","mercado de trabalho",
    "clt","pejotização","terceirização",
    // Custos e preços
    "combustível","gasolina","diesel","etanol","energia elétrica","conta de luz",
    "tarifa","aluguel","imóvel","construção civil","custo de vida","preço",
    "reajuste","alta de preços","queda de preços",
    // Impostos
    "imposto de renda","ir ","declaração","receita federal","reforma tributária",
    "imposto","tributo","iof","icms","iss","pis","cofins",
    // Macro e empresas
    "recessão","crescimento econômico","superávit","déficit","dívida pública",
    "orçamento","lula","haddad","fazenda","privatização","concessão",
    "resultado fiscal","arrecadação","exportação","importação","balança comercial",
    "indústria","produção industrial","varejo","serviços","agronegócio",
    "petrobras","vale","embraer","banco do brasil","caixa econômica",
    "nubank","itaú","bradesco","santander","xp",
    // Termos gerais
    "economia","econômico","econômica","financeiro","financeira","finanças"
];

// ─── Blacklist: se o título tiver APENAS esses temas, descartar ────────────
$blacklist_temas = [
    // Esporte
    "futebol","gol","campeonato","copa do mundo","libertadores","brasileirão",
    "flamengo","palmeiras","corinthians","são paulo","grêmio","internacional",
    "cruzeiro","atlético","santos","fluminense","vasco","botafogo","bahia",
    "escalação","goleiro","zagueiro","atacante","jogador","técnico demitido",
    "transferência de jogador","nba","nfl","fórmula 1","tênis","basquete",
    "olimpíadas esporte","atletismo","natação","ciclismo","handebol",
    // Entretenimento e celebridades
    "bbb","big brother","reality show","novela","ator","atriz","cantor","cantora",
    "música nova","clipe","show musical","festival de música","grammy","oscar",
    "casamento de famoso","separação de famoso","gravidez de",
    // Polícia e crime genérico (sem impacto econômico)
    "assassinato","homicídio","baleado","tiroteio","sequestro","latrocínio",
    "tráfico de drogas","preso em flagrante",
    // Saúde sem impacto econômico
    "dengue","gripe","vacina contra gripe","zika",
    // Clima sem impacto econômico
    "previsão do tempo","chuva forte","tempestade",
];

// ─── Funções auxiliares ────────────────────────────────────────────────────

/**
 * Verifica se a notícia é de economia/finanças.
 * Retorna false se for claramente de outro tema (blacklist).
 * Retorna false se não tiver nenhuma palavra econômica.
 */
function is_economica(string $titulo, string $resumo, array $palavras_eco, array $blacklist): bool {
    $texto = mb_strtolower($titulo . " " . $resumo);

    // Checar blacklist: se o TÍTULO contém termo da blacklist sem nenhum econômico, descartar
    $titulo_lower = mb_strtolower($titulo);
    $tem_blacklist_no_titulo = false;
    foreach ($blacklist as $b) {
        if (mb_strpos($titulo_lower, $b) !== false) {
            $tem_blacklist_no_titulo = true;
            break;
        }
    }

    // Checar se há ao menos 1 palavra econômica no título ou resumo
    $tem_economico = false;
    foreach ($palavras_eco as $p) {
        if (mb_strpos($texto, $p) !== false) {
            $tem_economico = true;
            break;
        }
    }

    // Se tem blacklist no título E nenhum econômico: descartar
    if ($tem_blacklist_no_titulo && !$tem_economico) return false;

    // Se não tem nenhum econômico: descartar
    return $tem_economico;
}

function calcular_relevancia(string $titulo, string $resumo, array $palavras): string {
    $texto = mb_strtolower($titulo . " " . $resumo);
    $pontos = 0;
    foreach ($palavras as $p) {
        if (mb_strpos($texto, $p) !== false) $pontos++;
    }
    if ($pontos >= 4) return "alto";
    if ($pontos >= 2) return "medio";
    return "baixo";
}

function limpar_html(string $text): string {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function formatar_data(string $dateStr): string {
    if (empty($dateStr)) return date("d/m/Y H:i");
    try {
        $ts = strtotime($dateStr);
        if ($ts === false) return date("d/m/Y H:i");
        return date("d/m/Y H:i", $ts);
    } catch (Exception $e) {
        return date("d/m/Y H:i");
    }
}

function fetch_feed(string $url): ?SimpleXMLElement {
    $ctx = stream_context_create([
        'http' => [
            'timeout'       => 10,
            'user_agent'    => 'Mozilla/5.0 (compatible; InvestAI/1.0; RSS Reader)',
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]
    ]);

    $xml_str = @file_get_contents($url, false, $ctx);
    if ($xml_str === false) return null;

    // Remove namespaces que podem travar o SimpleXML
    $xml_str = preg_replace('/(<\/?)[a-zA-Z]+:/', '$1', $xml_str);

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml_str);
    libxml_clear_errors();

    return $xml ?: null;
}

// ─── Coleta de Notícias ────────────────────────────────────────────────────
$todas_noticias = [];

foreach ($feeds as $feed_info) {
    $xml = fetch_feed($feed_info['url']);
    if (!$xml) continue;

    $items = [];
    // Tenta RSS 2.0
    if (isset($xml->channel->item)) {
        $items = $xml->channel->item;
    }
    // Tenta Atom
    elseif (isset($xml->entry)) {
        $items = $xml->entry;
    }

    $count = 0;
    foreach ($items as $item) {
        if ($count >= 10) break;

        $titulo = limpar_html((string)($item->title ?? ''));
        if (empty($titulo)) continue;

        $resumo = (string)($item->description ?? $item->summary ?? $item->content ?? '');
        $resumo = limpar_html($resumo);
        if (mb_strlen($resumo) > 300) {
            $resumo = mb_substr($resumo, 0, 297) . "...";
        }
        if (empty($resumo)) $resumo = "Sem resumo disponível.";

        // ─── FILTRO: apenas notícias de economia/finanças ──────────────
        if (!is_economica($titulo, $resumo, $palavras_economicas, $blacklist_temas)) {
            continue; // descarta a notícia
        }

        // Link
        $link = (string)($item->link ?? $item->id ?? '#');
        if ($link === '' && isset($item->link['href'])) {
            $link = (string)$item->link['href'];
        }

        // Data
        $data_raw = (string)($item->pubDate ?? $item->published ?? $item->updated ?? '');
        $data = formatar_data($data_raw);

        $relevancia = calcular_relevancia($titulo, $resumo, $palavras_economicas);

        $todas_noticias[] = [
            "titulo"      => $titulo,
            "resumo"      => $resumo,
            "fonte"       => $feed_info['fonte'],
            "cor_fonte"   => $feed_info['cor'],
            "icone_fonte" => $feed_info['icone'],
            "url"         => $link,
            "data"        => $data,
            "relevancia"  => $relevancia,
        ];

        $count++;
    }
}

// Ordena: alto > medio > baixo
$ordem = ["alto" => 0, "medio" => 1, "baixo" => 2];
usort($todas_noticias, fn($a, $b) =>
    ($ordem[$a['relevancia']] ?? 9) <=> ($ordem[$b['relevancia']] ?? 9)
);

// Máximo 20
$todas_noticias = array_slice($todas_noticias, 0, 20);

if (empty($todas_noticias)) {
    echo json_encode([
        "status"   => "error",
        "message"  => "Não foi possível carregar os feeds RSS. Verifique a conexão com a internet.",
        "noticias" => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

ob_clean(); // descarta qualquer output espúrio antes do JSON
echo json_encode([
    "status"   => "ok",
    "noticias" => $todas_noticias,
], JSON_UNESCAPED_UNICODE);
