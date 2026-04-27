<?php
/**
 * backend/api/noticias/explain.php
 * Recebe uma notícia e retorna uma explicação didática via Gemini AI.
 */


ob_start();
error_reporting(0); // Silenciar avisos para não quebrar o JSON
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');

session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception("Não autorizado.", 401);
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $noticia = $body['noticia'] ?? null;

    if (!$noticia || empty($noticia['titulo'])) {
        http_response_code(400);
        ob_clean();
        echo json_encode(["status" => "error", "mensagem" => "Dados da notícia inválidos."]);
        exit;
    }

    // ─── Chave Gemini ──────────────────────────────────────────────────────────
    function get_gemini_key(): ?string
    {
        $key = getenv('GEMINI_API_KEY');
        if ($key)
            return trim($key);
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
        echo json_encode(["status" => "sem_chave", "mensagem" => "Chave Gemini API não configurada."]);
        exit;
    }

    // ─── Perfil do usuário (para personalizar a explicação) ──────────────────
    $root = dirname(dirname(dirname(dirname(__FILE__))));
    require_once $root . '/DataBase/conexao.php';

    $usuario_id = $_SESSION['usuario_id'];
    $inicio_mes = date('Y-m-01');
    $hoje = date('Y-m-d');

    $stmt = $conexao->prepare("SELECT saldo_inicial, renda_mensal, objetivo_financeiro FROM perfil_financeiro WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $perfil = $stmt->get_result()->fetch_assoc();

    $stmt = $conexao->prepare("SELECT COALESCE(SUM(valor),0) as total FROM ganhos WHERE usuario_id=? AND data_ganho BETWEEN ? AND ?");
    $stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
    $stmt->execute();
    $ganhos = floatval($stmt->get_result()->fetch_assoc()['total']);

    $stmt = $conexao->prepare("SELECT COALESCE(SUM(valor),0) as total FROM despesas WHERE usuario_id=? AND data_despesa BETWEEN ? AND ?");
    $stmt->bind_param("iss", $usuario_id, $inicio_mes, $hoje);
    $stmt->execute();
    $despesas = floatval($stmt->get_result()->fetch_assoc()['total']);

    $saldo_atual = floatval($perfil['saldo_inicial'] ?? 0) + $ganhos - $despesas;
    $renda = floatval($perfil['renda_mensal'] ?? 0);
    $objetivo = $perfil['objetivo_financeiro'] ?? 'Não informado';

    // ─── Buscar contexto de gastos do usuário ─────────────────────────────────
    $contexto_gastos = "Sem gastos registrados";
    try {
        $stmt_gastos = $conexao->prepare("SELECT categoria, SUM(valor) as total FROM despesas WHERE usuario_id = ? GROUP BY categoria ORDER BY total DESC LIMIT 3");
        if ($stmt_gastos) {
            $stmt_gastos->bind_param("i", $usuario_id);
            $stmt_gastos->execute();
            $res_gastos = $stmt_gastos->get_result();
            $categorias_top = [];
            while ($rg = $res_gastos->fetch_assoc())
                $categorias_top[] = $rg['categoria'];
            if (!empty($categorias_top)) {
                $contexto_gastos = implode(", ", $categorias_top);
            }
        }
    } catch (Exception $e) {
        // Silencioso: usa o padrão
    }

    // ─── Montar Prompt Avançado ────────────────────────────────────────────────
    $titulo = $noticia['titulo'] ?? '';
    $resumo = $noticia['resumo'] ?? '';
    $fonte = $noticia['fonte'] ?? '';
    $data = $noticia['data'] ?? '';
    $saldo_f = number_format($saldo_atual, 2, ',', '.');
    $renda_f = number_format($renda, 2, ',', '.');

    $prompt = <<<PROMPT
Você é o Analista Econômico Chefe do InvestAI. 
Entregue um relatório de inteligência financeira PROFUNDO e ACIONÁVEL.

PERFIL FINANCEIRO DO USUÁRIO:
- Saldo atual disponível: R$ {$saldo_f}
- Renda mensal declarada: R$ {$renda_f}
- Comportamento de gastos (Categorias mais usadas): {$contexto_gastos}
- Objetivo financeiro principal: {$objetivo}

DADOS DA NOTÍCIA ORIGINAL:
- Título: {$titulo}
- Fonte: {$fonte}
- Resumo Bruto: {$resumo}

DIRETRIZES DE ESCRITA (OBRIGATÓRIO):
1. Profundidade Institucional: Escreva como um relatório premium. Desenvolva as ideias. Não use respostas curtas.
2. Efeito Cascata: Explique não apenas o que aconteceu, mas os efeitos de segunda ordem (ex: se o petróleo sobe, o frete sobe, a inflação sobe, a Selic não cai).
3. Personalização Extrema: Você DEVE cruzar o fato com a renda, saldo, gastos e objetivo do usuário.
4. Responda APENAS com um objeto JSON puro e válido. Sem blocos de código (```json), sem introduções.

ESTRUTURA JSON:
{
  "manchete": "Título focado no impacto (máx 90 carac)",
  "resumo_executivo": "Contexto e fato central (2-3 frases).",
  "analise_de_cenario": "Desdobramentos macro e reação do mercado (2-3 frases).",
  "impacto_bolso_e_metas": "Como afeta os gastos e o objetivo '{$objetivo}' do usuário (2-3 frases).",
  "indicadores_afetados": ["Indicador + Tendência (Alta/Baixa/Estável)"],
  "plano_de_acao": ["Ação imediata", "Ação de médio prazo"],
  "glossario_tecnico": [{"termo": "Termo", "definicao": "Explicação simples"}],
  "nivel_impacto": "Alto|Medio|Baixo"
}
PROMPT;

    // ─── Sistema de Cache ───────────────────────────────────────────────────────
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/DataBase/conexao.php';
    
    // Gerar uma chave única para esta notícia (Assinatura)
    $noticia_id_cache = md5($noticia['titulo'] . ($noticia['fonte'] ?? ''));
    
    // Verificar se já existe análise salva para este usuário
    $stmt_cache = $conexao->prepare("SELECT resposta_ia FROM noticias_ai WHERE usuario_id = ? AND noticia_hash = ?");
    $stmt_cache->bind_param("is", $_SESSION['usuario_id'], $noticia_id_cache);
    $stmt_cache->execute();
    $res_cache = $stmt_cache->get_result();
    
    if ($res_cache->num_rows > 0) {
        $cache_data = $res_cache->fetch_assoc();
        $resultado = json_decode($cache_data['resposta_ia'], true);
        $resultado['status'] = 'ok';
        $resultado['ai_source'] = 'cache'; 
        
        ob_clean();
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Se não houver cache, chamar IA ──────────────────────────────────────────
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/backend/includes/ai_handler.php';

    $ai_res = call_ai_service($prompt, [
        'temperature' => 0.4, // Menor temperatura para JSON mais estável
        'max_tokens' => 1200,
        'ollama_model' => 'llama3'

    ]);

    if (!$ai_res['success']) {
        ob_clean();
        echo json_encode([
            "status" => "error",
            "mensagem" => "Falha no processamento de IA: " . $ai_res['message']
        ]);
        exit;
    }

    $raw = clean_ai_json($ai_res['data']);

    // Tentar decodificar
    $resultado = json_decode($raw, true);

    // Se falhar, tentar um reparo simples (fechar chaves se estiver truncado)
    if (json_last_error() !== JSON_ERROR_NONE) {
        $reparado = trim($raw);
        if (substr($reparado, -1) !== '}') $reparado .= '}';
        $resultado = json_decode($reparado, true);
    }

    if (json_last_error() !== JSON_ERROR_NONE) {
        $erro_json = json_last_error_msg();
        // Logar o erro técnico para depuração
        file_put_contents(dirname(dirname(dirname(__FILE__))) . '/ai_debug.log', "JSON Error: $erro_json | Final: " . substr($raw, -30) . "\n", FILE_APPEND);
        
        throw new Exception("IA instável (Erro: $erro_json). Tente novamente em instantes.");
    }

    $resultado['status'] = 'ok';
    $resultado['ai_source'] = $ai_res['source'];

    // Salvar no Cache para visitas futuras
    $json_final = json_encode($resultado, JSON_UNESCAPED_UNICODE);
    $stmt_save = $conexao->prepare("INSERT INTO noticias_ai (usuario_id, noticia_hash, resposta_ia) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE resposta_ia = ?");
    $stmt_save->bind_param("isss", $_SESSION['usuario_id'], $noticia_id_cache, $json_final, $json_final);
    $stmt_save->execute();

    ob_clean();
    echo $json_final;

} catch (Exception $e) {
    ob_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "status" => "error",
        "mensagem" => $e->getMessage()
    ]);
}
