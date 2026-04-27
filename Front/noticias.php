<?php
/**
 * Front/noticias.php — Página de Notícias & IA do InvestAI
 * Carrega notícias do banco diretamente no PHP (sem fetch), garantindo sessão.
 */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$nome = htmlspecialchars($_SESSION['usuario_nome']);
$usuarioId = (int) $_SESSION['usuario_id'];

// ─── Carregar notícias do banco diretamente ───────────────────────────────────
$root = dirname(dirname(__FILE__));
require_once $root . '/DataBase/conexao.php';

// Categorias de despesas do usuário (para cruzamento)
$mapaCategorias = [
    'Transporte' => ['combustível', 'gasolina', 'diesel', 'etanol', 'ônibus', 'metrô', 'uber', 'frete', 'carro', 'táxi', 'pedágio'],
    'Alimentação' => ['aliment', 'comida', 'supermercado', 'mercado', 'restaurante', 'lanchonete', 'delivery', 'ifood', 'rappi', 'refeição', 'cesta'],
    'Moradia' => ['aluguel', 'condomínio', 'iptu', 'água', 'luz', 'energia', 'gás', 'internet', 'reforma', 'imóvel'],
    'Lazer' => ['cinema', 'netflix', 'spotify', 'disney', 'viagem', 'hotel', 'passagem', 'academia', 'streaming', 'show', 'bar', 'festa'],
    'Tecnologia' => ['celular', 'smartphone', 'computador', 'notebook', 'software', 'aplicativo', 'internet', 'tecnologia', 'eletrônico'],
    'Saúde' => ['saúde', 'plano', 'consulta', 'médico', 'dentista', 'remédio', 'medicamento', 'farmácia', 'hospital', 'exame'],
    'Finanças Gerais' => ['investimento', 'poupança', 'tesouro', 'fundo', 'ação', 'bolsa', 'seguro', 'previdência', 'imposto', 'conta', 'banco', 'empréstimo', 'cartão', 'crédito'],
];

$stmt = $conexao->prepare("SELECT descricao FROM despesas WHERE usuario_id = ? AND data_despesa >= DATE_FORMAT(NOW(),'%Y-%m-01') LIMIT 30");
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$res = $stmt->get_result();
$descDespesas = [];
while ($row = $res->fetch_assoc())
    $descDespesas[] = mb_strtolower($row['descricao']);

$categoriasUsuario = [];
foreach ($descDespesas as $desc) {
    foreach ($mapaCategorias as $cat => $palavras) {
        if (in_array($cat, $categoriasUsuario))
            continue;
        foreach ($palavras as $p) {
            if (mb_strpos($desc, $p) !== false) {
                $categoriasUsuario[] = $cat;
                break;
            }
        }
    }
}
$categoriasUsuario = array_unique($categoriasUsuario);

// Buscar notícias
$stmt2 = $conexao->prepare(
    "SELECT id, titulo, fonte, url, resumo, categoria, nivel_impacto,
            cenario_hipotetico, acoes_praticas, sugestao_investimento,
            dica_economia, cor_fonte, icone_fonte, data_publicacao
     FROM noticias_financeiras
     WHERE processado_ia = 1
     ORDER BY data_publicacao DESC, criado_em DESC
     LIMIT 60"
);
$stmt2->execute();
$res2 = $stmt2->get_result();
$noticias = [];
$contagemCat = [];
while ($row = $res2->fetch_assoc()) {
    $cat = $row['categoria'] ?: 'Finanças Gerais';
    $acoes = json_decode($row['acoes_praticas'] ?? '[]', true);
    $dataFmt = $row['data_publicacao']
        ? date('d/m/Y H:i', strtotime($row['data_publicacao']))
        : '—';
    $contagemCat[$cat] = ($contagemCat[$cat] ?? 0) + 1;
    $noticias[] = [
        'id' => (int) $row['id'],
        'titulo' => $row['titulo'],
        'fonte' => $row['fonte'],
        'url' => $row['url'],
        'resumo' => $row['resumo'],
        'categoria' => $cat,
        'nivel_impacto' => $row['nivel_impacto'] ?? 'baixo',
        'cenario_hipotetico' => $row['cenario_hipotetico'] ?? '',
        'acoes_praticas' => $acoes ?: [],
        'sugestao_investimento' => $row['sugestao_investimento'] ?? '',
        'dica_economia' => $row['dica_economia'] ?? '',
        'cor_fonte' => $row['cor_fonte'] ?? '#6366f1',
        'icone_fonte' => $row['icone_fonte'] ?? 'bi-newspaper',
        'data' => $dataFmt,
        'impacto_pessoal' => in_array($cat, $categoriasUsuario),
    ];
}

// Stats
$totalNoticias = count($noticias);
$totalRelevantes = count(array_filter($noticias, fn($n) => $n['impacto_pessoal']));

// Última atualização
$stmt3 = $conexao->prepare("SELECT MAX(criado_em) as ultima FROM noticias_financeiras WHERE processado_ia = 1");
$stmt3->execute();
$ultimaRow = $stmt3->get_result()->fetch_assoc();
$ultimaAtualiz = $ultimaRow['ultima']
    ? date('d/m/Y \à\s H:i', strtotime($ultimaRow['ultima']))
    : null;

// Serializar para JS
$noticiasJson = json_encode($noticias, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$contagemJson = json_encode($contagemCat, JSON_UNESCAPED_UNICODE);
$categoriaUserJson = json_encode(array_values($categoriasUsuario), JSON_UNESCAPED_UNICODE);
$ultimaJson = json_encode($ultimaAtualiz);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestAi — Notícias & IA</title>
    <meta name="description"
        content="Notícias econômicas categorizadas por IA com análise de impacto personalizada para o seu perfil financeiro no InvestAi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/style/css/animations.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/style/css/navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/style/css/internal-pages.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/style/css/noticias.css?v=<?= time() ?>">
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-custom">
        <div class="container d-flex align-items-center justify-content-between" style="max-width:1200px;">
            <a href="dashboard.php" class="logo">
                <svg class="neural-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 18L9 13M9 13L15 15M15 15L20 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="4" cy="18" r="2" fill="currentColor"/>
                    <circle cx="9" cy="13" r="2" fill="currentColor"/>
                    <circle cx="15" cy="15" r="2" fill="currentColor"/>
                    <circle cx="20" cy="6" r="3" fill="var(--brand-accent)"/>
                </svg>
                Invest<span>AI</span>
            </a>
            <div class="d-flex align-items-center gap-4">
                <a href="dashboard.php" class="nav-link-custom">Dashboard</a>
                <a href="resumo.php" class="nav-link-custom nav-resumo">Resumo Financeiro</a>
                <a href="ganhos.php" class="nav-link-custom nav-ganhos">Ganhos</a>
                <a href="despesas.php" class="nav-link-custom nav-despesas">Despesas</a>
                <a href="noticias.php" class="nav-link-custom active nav-noticias">Notícias IA</a>
                <a href="perfil.php" class="user-badge"><i class="bi bi-person-fill me-1"></i><?= $nome ?></a>
                <a href="logout.php" class="nav-link-custom" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </nav>

    <!-- Dados injetados pelo PHP — sem fetch, sem problema de sessão -->
    <script>
        window.INVESTAI_DATA = {
            noticias: <?= $noticiasJson ?>,
            contagemCat: <?= $contagemJson ?>,
            categoriasUsuario: <?= $categoriaUserJson ?>,
            ultimaAtualizacao: <?= $ultimaJson ?>,
            total: <?= $totalNoticias ?>,
            totalRelevantes: <?= $totalRelevantes ?>,
        };
    </script>

    <div class="main-container" style="max-width:1200px;">

        <!-- ===== HEADER ===== -->
        <div class="page-header">
            <h1><i class="bi bi-newspaper"></i>Notícias & IA</h1>
            <p>Curadoria inteligente de notícias financeiras com análise de impacto personalizada para o seu perfil.</p>
        </div>

        <!-- ===== HERO ===== -->
        <div class="hero-ia">
            <div class="hero-ia-content">
                <div class="hero-badge"><i class="bi bi-stars"></i>Powered by Gemini AI</div>
                <h2>Radar Econômico Personalizado</h2>
                <p>
                    Notícias de G1 Economia, InfoMoney e Investing.com — filtradas e categorizadas.
                    Clique em <strong>Atualizar</strong> para buscar novas notícias ou em <strong>Analisar com
                        IA</strong>
                    para cruzar o cenário econômico com os seus gastos reais.
                </p>
            </div>
            <div class="hero-ia-actions">
                <div class="hero-stats-row">
                    <div class="stat-chip">
                        <i class="bi bi-newspaper"></i>
                        <span id="stat-total"><?= $totalNoticias ?></span> notícias
                    </div>
                    <div class="stat-chip destaque">
                        <i class="bi bi-bell-fill"></i>
                        <span id="stat-relevantes"><?= $totalRelevantes ?></span> afetam você
                    </div>
                </div>
                <div class="hero-btns-row">
                    <button id="btn-atualizar" class="btn-atualizar">
                        <i class="bi bi-arrow-repeat"></i>Atualizar
                    </button>
                    <button id="btn-analisar" class="btn-analisar" <?= $totalNoticias === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-stars"></i>Analisar com IA
                    </button>
                </div>
                <div class="ultima-atualizacao" id="ultima-atualizacao">
                    <?= $ultimaAtualiz ? "Atualizado: {$ultimaAtualiz}" : 'Nunca atualizado — clique em Atualizar' ?>
                </div>
            </div>
        </div>

        <!-- ===== PAINEL IA ===== -->
        <div id="ia-panel" style="display:none; margin-bottom:30px;">
            <div class="ia-panel" id="ia-panel-inner">
                <div class="ia-loading" id="ia-loading">
                    <div class="ia-spinner"></div>
                    <span>A IA está analisando o mercado para o seu perfil...</span>
                </div>
                <div id="ia-conteudo"></div>
            </div>
        </div>

        <!-- ===== TOOLBAR: FONTES ===== -->
        <div class="noticias-toolbar">
            <div class="fonte-filters">
                <button class="fonte-btn active" data-fonte="todas">Todas</button>
                <button class="fonte-btn" data-fonte="G1 Economia">G1 Economia</button>
                <button class="fonte-btn" data-fonte="InfoMoney">InfoMoney</button>
                <button class="fonte-btn" data-fonte="Investing.com">Investing.com</button>
            </div>
            <span class="noticias-count" id="noticias-count"><?= $totalNoticias ?> notícias</span>
        </div>

        <!-- ===== FILTROS DE CATEGORIA ===== -->
        <div class="categoria-filters" id="categoria-filters"></div>

        <!-- ===== GRID DE NOTÍCIAS ===== -->
        <div class="noticias-grid" id="noticias-grid">
            <?php if ($totalNoticias === 0): ?>
                <div class="empty-state" style="grid-column:1/-1">
                    <i class="bi bi-newspaper"></i>
                    <p>Nenhuma notícia ainda.<br>
                        <small>Clique em <strong>Atualizar</strong> para buscar agora.</small>
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Toast de feedback -->
    <div class="news-toast" id="news-toast" role="alert">
        <i class="bi bi-info-circle-fill"></i>
        <span class="toast-msg"></span>
    </div>

    <script src="api/noticias/noticias.js?v=<?= time() ?>"></script>
</body>

</html>