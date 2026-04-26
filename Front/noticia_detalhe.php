<?php
/**
 * Front/noticia_detalhe.php
 * Página de detalhe de notícia com explicação didática via Gemini AI.
 */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$nome = htmlspecialchars($_SESSION['usuario_nome']);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestAi — Entendendo a Notícia</title>
    <meta name="description" content="Análise didática de notícias financeiras com IA personalizada pelo InvestAi.">

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
    <link rel="stylesheet" href="assets/style/css/noticia_detalhe.css?v=<?= time() ?>">
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-custom">
        <div class="container d-flex align-items-center justify-content-between" style="max-width:960px;">
            <a href="dashboard.php" class="logo">
                <svg class="neural-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 18L9 13M9 13L15 15M15 15L20 6" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" />
                    <circle cx="4" cy="18" r="2" fill="currentColor" />
                    <circle cx="9" cy="13" r="2" fill="currentColor" />
                    <circle cx="15" cy="15" r="2" fill="currentColor" />
                    <circle cx="20" cy="6" r="3" fill="var(--brand-accent)" />
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

    <div class="main-container detalhe-container">

        <!-- BREADCRUMB -->
        <div class="breadcrumb-bar">
            <a href="noticias.php" id="btn-voltar">
                <i class="bi bi-arrow-left"></i> Voltar às Notícias
            </a>
        </div>

        <!-- HEADER DA NOTÍCIA -->
        <div class="detalhe-header" id="detalhe-header">
            <div class="detalhe-meta" id="detalhe-meta">
                <!-- preenchido via JS -->
            </div>
            <h1 class="detalhe-titulo" id="detalhe-titulo">Carregando notícia...</h1>
            <div class="detalhe-resumo-original" id="detalhe-resumo-original"></div>

            <!-- BOTÃO NOTÍCIA ORIGINAL -->
            <a href="#" id="btn-noticia-original" class="btn-original" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i>
                Ler notícia original
            </a>
        </div>

        <!-- SEÇÃO IA -->
        <div class="ia-detalhe-section">
            <!-- Estado de loading -->
            <div class="ia-detalhe-loading" id="ia-loading">
                <div class="loading-orb">
                    <div class="orb-spinner"></div>
                    <div class="orb-pulse"></div>
                </div>
                <div class="loading-text">
                    <h3>A IA está lendo a notícia…</h3>
                    <p>Preparando uma explicação clara e personalizada para o seu perfil financeiro.</p>
                </div>
            </div>

            <!-- Conteúdo da explicação IA -->
            <div id="ia-conteudo" style="display:none;"></div>
        </div>

    </div>

    <script src="api/noticias/noticia_detalhe.js?v=<?= time() ?>"></script>
</body>

</html>