<?php
/**
 * backend/api/noticias/analyze.php
 * Analisa notícias via Gemini com categorização obrigatória em 7 tags,
 * 3 ações práticas por notícia e cruzamento com despesas do usuário no banco.
 */

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');

// ─── Autenticação ───────────────────────────────────────────────────────────
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Não autorizado."]);
    exit;
}

// ─── Corpo da requisição ────────────────────────────────────────────────────
$body     = json_decode(file_get_contents('php://input'), true);
$noticias = $body['noticias'] ?? [];

if (empty($noticias)) {
    http_response_code(400);
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Nenhuma notícia recebida para análise."]);
    exit;
}

// ─── Chave Gemini ───────────────────────────────────────────────────────────
function get_gemini_key(): ?string {
    $key = getenv('GEMINI_API_KEY');
    if ($key) return trim($key);

    $env_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/.env';
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

$gemini_key = get_gemini_key();
if (!$gemini_key) {
    ob_clean();
    echo json_encode([
        "status"            => "sem_chave",
        "mensagem"          => "Chave Gemini API não configurada.",
        "resumo_geral"      => "Configure a chave da API Gemini para ativar a análise de IA personalizada.",
        "nivel_alerta"      => "baixo",
        "analises"          => [],
        "top_acao_da_semana" => "Configure sua chave Gemini API para receber recomendações personalizadas.",
        "categorias_usuario" => []
    ]);
    exit;
}

// ─── Perfil financeiro e despesas do banco ──────────────────────────────────
$root    = dirname(dirname(dirname(dirname(__FILE__))));
require_once $root . '/DataBase/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$hoje       = date('Y-m-d');
$inicio_mes = date('Y-m-01');

// Perfil
$stmt = $conexao->prepare(
    "SELECT saldo_inicial, renda_mensal, objetivo_financeiro, perfil_comportamento
     FROM perfil_financeiro WHERE usuario_id = ?"
);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc();

// Ganhos do mês
$stmt = $conexao->prepare(
    "SELECT COALESCE(SUM(valor), 0) as total FROM ganhos WHERE usuario_id = ? AND data_ganho BETWEEN ? AND ?"
);
$stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
$stmt->execute();
$total_ganhos = floatval($stmt->get_result()->fetch_assoc()['total']);

// Despesas do mês
$stmt = $conexao->prepare(
    "SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ?"
);
$stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
$stmt->execute();
$total_despesas = floatval($stmt->get_result()->fetch_assoc()['total']);

// Descrições de despesas (para cruzamento com categorias da IA)
$stmt = $conexao->prepare(
    "SELECT descricao FROM despesas WHERE usuario_id = ? AND data_despesa BETWEEN ? AND ? ORDER BY valor DESC LIMIT 20"
);
$stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
$stmt->execute();
$res = $stmt->get_result();
$descricoes_despesas = [];
while ($row = $res->fetch_assoc()) {
    $descricoes_despesas[] = mb_strtolower($row['descricao']);
}

$saldo_atual = floatval($perfil['saldo_inicial'] ?? 0) + $total_ganhos - $total_despesas;

// ─── Mapeamento de palavras-chave → categoria ────────────────────────────────
/**
 * Mapeia os textos livres das descrições de despesas do usuário para
 * as 7 categorias fixas do sistema.
 */
$mapa_categorias = [
    'Transporte'      => ['combustível','gasolina','diesel','etanol','ônibus','metro','metrô','uber','99','táxi','táxi','passagem','trem','bicicleta','estacionamento','pedágio','frete','moto','gás veículo','carro'],
    'Alimentação'     => ['aliment','comida','supermercado','mercado','feira','restaurante','lanchonete','padaria','açougue','hortifruti','cesta básica','delivery','ifood','rappi','pizza','hambúrguer','café','refeição','jantar','almoço','café da manhã'],
    'Moradia'         => ['aluguel','condomínio','iptu','água','luz','energia elétrica','gás','internet residencial','reforma','construção','imóvel','apartamento','casa','financiamento imóvel','aluguel'],
    'Lazer'           => ['cinema','netflix','spotify','disney','entretenimento','viagem','hotel','passagem aérea','turismo','esporte','academia','jogo','show','evento','teatro','bar','festa','lazer','streaming'],
    'Tecnologia'      => ['celular','smartphone','computador','notebook','tablet','software','aplicativo','assinatura digital','internet','plano de dados','tecnologia','eletrônico','gadget','impressora'],
    'Saúde'           => ['saúde','plano de saúde','consulta','médico','dentista','remédio','medicamento','farmácia','hospital','clínica','exame','laboratório','fisioterapia','academia saúde','vacina','psicólogo'],
    'Finanças Gerais' => ['investimento','poupança','tesouro','fundo','ação','bolsa','seguro','previdência','imposto','ir','conta','banco','empréstimo','financiamento','cartão','crédito','débito','taxa','tarifa'],
];

/**
 * Retorna o conjunto de categorias que o usuário TEM despesas cadastradas,
 * cruzando as descrições com o mapa de palavras-chave.
 */
function mapear_categorias_usuario(array $descricoes, array $mapa): array {
    $encontradas = [];
    foreach ($descricoes as $desc) {
        foreach ($mapa as $categoria => $palavras) {
            if (in_array($categoria, $encontradas)) continue;
            foreach ($palavras as $p) {
                if (mb_strpos($desc, $p) !== false) {
                    $encontradas[] = $categoria;
                    break;
                }
            }
        }
    }
    return array_values(array_unique($encontradas));
}

$categorias_usuario = mapear_categorias_usuario($descricoes_despesas, $mapa_categorias);

// ─── Lógica de Cache ────────────────────────────────────────────────────────
require_once dirname(dirname(dirname(__FILE__))) . '/includes/ai_handler.php';

$analises_finais = [];
$noticias_para_processar = [];

// Gerar uma chave de categorias (ordenada para consistência)
sort($categorias_usuario);
$cat_key = implode(',', $categorias_usuario);
$perfil_c = $perfil['perfil_comportamento'] ?? 'moderado';

foreach ($noticias as $n) {
    $url_hash = md5($n['url'] ?? $n['titulo']);
    
    // Tenta buscar no cache (últimas 24h)
    $stmt_cache = $conexao->prepare("SELECT analise_json FROM cache_ia_noticias WHERE noticia_url_hash = ? AND perfil_usuario = ? AND criado_em > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");
    $stmt_cache->bind_param("ss", $url_hash, $perfil_c);
    $stmt_cache->execute();
    $res_cache = $stmt_cache->get_result();
    
    if ($row_cache = $res_cache->fetch_assoc()) {
        $analise_cached = json_decode($row_cache['analise_json'], true);
        $analises_finais[] = $analise_cached;
    } else {
        $noticias_para_processar[] = $n;
    }
}

// Se todas as notícias já estavam no cache, retornamos imediatamente
if (empty($noticias_para_processar)) {
    ob_clean();
    echo json_encode([
        "status"            => "ok",
        "source"            => "cache",
        "analises"          => $analises_finais,
        "resumo_geral"      => "Todas as análises foram recuperadas do cache inteligente para o seu perfil.",
        "nivel_alerta"      => "baixo",
        "top_acao_da_semana"=> "Suas recomendações estão atualizadas com base no mercado.",
        "categorias_usuario"=> $categorias_usuario
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Chamar IA (Apenas para o que não está no cache) ───────────────────────
$noticias_texto = "";
foreach (array_slice($noticias_para_processar, 0, 10) as $i => $n) {
    $idx    = $i + 1;
    $fonte  = $n['fonte']  ?? '';
    $titulo = $n['titulo'] ?? '';
    $resumo = mb_substr($n['resumo'] ?? '', 0, 200);
    $noticias_texto .= "\n{$idx}. [{$fonte}] {$titulo}\n   Resumo: {$resumo}\n";
}

$categorias_usuario_str = !empty($categorias_usuario) ? implode(", ", $categorias_usuario) : "não identificadas";
$objetivo  = $perfil['objetivo_financeiro']  ?? 'Não informado';
$renda     = number_format(floatval($perfil['renda_mensal'] ?? 0), 2, ',', '.');
$saldo_fmt = number_format($saldo_atual, 2, ',', '.');
$ganhos_f  = number_format($total_ganhos, 2, ',', '.');
$desp_f    = number_format($total_despesas, 2, ',', '.');

$prompt = <<<PROMPT
Você é o Arquiteto Financeiro do InvestAI. Analise as notícias econômicas abaixo e gere um relatório de impacto PERSONALIZADO.

PERFIL DO USUÁRIO:
- Saldo atual: R$ {$saldo_fmt}
- Renda mensal: R$ {$renda}
- Ganhos registrados no mês: R$ {$ganhos_f}
- Despesas registrados no mês: R$ {$desp_f}
- Objetivo financeiro: {$objetivo}
- Perfil de comportamento: {$perfil_c}
- Categorias de gasto do usuário: {$categorias_usuario_str}

NOTÍCIAS NOVAS PARA ANALISAR:
{$noticias_texto}

CATEGORIAS FIXAS PERMITIDAS:
["Transporte", "Alimentação", "Moradia", "Lazer", "Tecnologia", "Saúde", "Finanças Gerais"]

TAREFA:
Analise cada notícia e responda APENAS com JSON puro, seguindo exatamente esta estrutura:

{
  "resumo_geral": "Breve contexto do cenário atual",
  "nivel_alerta": "baixo|medio|alto",
  "analises": [
    {
      "titulo_noticia": "título exato",
      "categoria": "Uma das 7 fixas",
      "impacto": "alto|medio|baixo",
      "cenario_hipotetico": "1-2 frases sobre futuro",
      "como_afeta": "1 frase direta no bolso",
      "acoes_praticas": ["Ação 1", "Ação 2", "Ação 3"],
      "sugestao_investimento": "Sugestão",
      "dica_economia": "Dica"
    }
  ],
  "top_acao_da_semana": "Ação principal"
}
PROMPT;

$ai_res = call_ai_service($prompt, [
    'temperature' => 0.65,
    'max_tokens'  => 2500,
    'ollama_model'=> 'llama3'
]);

if (!$ai_res['success']) {
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "IA Offline: " . $ai_res['message']]);
    exit;
}

$raw = clean_ai_json($ai_res['data']);
$resultado = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($resultado['analises'])) {
    ob_clean();
    echo json_encode(["status" => "error", "mensagem" => "Erro no processamento do JSON da IA.", "raw" => $raw]);
    exit;
}

// ─── SALVAR NO CACHE E MESCLAR ──────────────────────────────────────────────
foreach ($resultado['analises'] as $nova_analise) {
    // Encontrar a URL original para o hash
    $titulo_original = $nova_analise['titulo_noticia'];
    $url_original = "";
    foreach($noticias_para_processar as $np) {
        if ($np['titulo'] === $titulo_original) {
            $url_original = $np['url'];
            break;
        }
    }
    
    $url_hash = md5($url_original ?: $titulo_original);
    $analise_json = json_encode($nova_analise, JSON_UNESCAPED_UNICODE);
    
    // Salva no banco para uso futuro
    $stmt_save = $conexao->prepare("INSERT INTO cache_ia_noticias (noticia_url_hash, perfil_usuario, categorias_usuario, analise_json) VALUES (?, ?, ?, ?)");
    $stmt_save->bind_param("ssss", $url_hash, $perfil_c, $cat_key, $analise_json);
    $stmt_save->execute();
    
    $analises_finais[] = $nova_analise;
}

// Cruzamento final de relevância
foreach ($analises_finais as &$af) {
    $af['relevante_para_usuario'] = in_array($af['categoria'], $categorias_usuario);
}

ob_clean();
echo json_encode([
    "status"            => "ok",
    "source"            => $ai_res['source'],
    "resumo_geral"      => $resultado['resumo_geral'] ?? "Análise financeira atualizada.",
    "nivel_alerta"      => $resultado['nivel_alerta'] ?? "baixo",
    "analises"          => $analises_finais,
    "top_acao_da_semana"=> $resultado['top_acao_da_semana'] ?? "",
    "categorias_usuario"=> $categorias_usuario
], JSON_UNESCAPED_UNICODE);

