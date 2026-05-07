<?php
/**
 * Front/noticia_detalhe.php
 * Página de detalhe de notícia com explicação didática via Gemini AI.
 */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../login.php');
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
    <link rel="stylesheet" href="../../../assets/style/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/animations.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/internal-pages.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/noticias.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/noticia_detalhe.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/footer.css?v=<?= time() ?>">
</head>

<body>

    <?php $nav_active = 'noticias'; include '../../components/navbar.php'; ?>

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

    <script src="../../../api/noticias/noticia_detalhe.js?v=<?= time() ?>"></script>

    <?php include '../../components/footer.php'; ?>
    <script src="../../../assets/style/js/legal-modals.js"></script>
</body>

</html>