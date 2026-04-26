<?php
session_start();

// Redirecionar se não logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$nome = htmlspecialchars($_SESSION['usuario_nome']);

$is_first_login = false;
if (isset($_SESSION['is_first_login']) && $_SESSION['is_first_login'] === true) {
    $is_first_login = true;
    // Remove flag to avoid showing the tour repeatedly
    unset($_SESSION['is_first_login']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestAi — Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/style/css/animations.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/style/css/navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/style/css/internal-pages.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/style/css/dashboard.css?v=<?= time() ?>">

    <?php if ($is_first_login): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css" />
    <?php endif; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-custom">
        <div class="container d-flex align-items-center justify-content-between" style="max-width:960px;">
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
                <a href="dashboard.php" class="nav-link-custom active">Dashboard</a>
                <a href="resumo.php" class="nav-link-custom nav-resumo">Resumo Financeiro</a>
                <a href="ganhos.php" class="nav-link-custom nav-ganhos">Ganhos</a>
                <a href="despesas.php" class="nav-link-custom nav-despesas">Despesas</a>
                <a href="noticias.php" class="nav-link-custom nav-noticias">Notícias IA</a>
                <a href="perfil.php" class="user-badge"><i class="bi bi-person-fill me-1"></i><?= $nome ?></a>
                <a href="logout.php" class="nav-link-custom" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </nav>

    <div class="main-container">

        <!-- ===== HEADER ===== -->
        <div class="page-header">
            <h1><i class="bi bi-speedometer2"></i>Dashboard</h1>
            <p>Visão geral de suas finanças.</p>
        </div>

        <!-- ===== LOADING STATE ===== -->
        <div id="loading" class="loading">
            <div class="loading-spinner"></div>
            <p class="text-secondary">Carregando dados...</p>
        </div>

        <!-- ===== CONTENT ===== -->
        <div id="content" style="display: none;">

            <!-- ===== CARDS DE RESUMO ===== -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="label"><i class="bi bi-wallet2 me-1"></i>Saldo Atual</div>
                    <div class="value" id="saldo-atual">R$ 0,00</div>
                </div>
                <div class="summary-card">
                    <div class="label"><i class="bi bi-percent me-1"></i>Saldo Inicial</div>
                    <div class="value" id="saldo-inicial">R$ 0,00</div>
                </div>
                <div class="summary-card">
                    <div class="label"><i class="bi bi-arrow-up-right me-1"></i>Total Ganhos</div>
                    <div class="value" id="total-ganhos">R$ 0,00</div>
                </div>
                <div class="summary-card">
                    <div class="label"><i class="bi bi-arrow-down-left me-1"></i>Total Despesas</div>
                    <div class="value" id="total-despesas">R$ 0,00</div>
                </div>
                <div class="summary-card">
                    <div class="label"><i class="bi bi-cash-stack me-1"></i>Renda Mensal</div>
                    <div class="value" id="renda-mensal">R$ 0,00</div>
                </div>
                <div class="summary-card">
                    <div class="label"><i class="bi bi-target me-1"></i>Objetivo Financeiro</div>
                    <div class="value" id="objetivo">Não definido</div>
                </div>
            </div>



        </div>

    </div>

    <script src="api/utils/shared.js?v=<?= time() ?>"></script>
    <script src="api/utils/nav.js?v=<?= time() ?>"></script>
    <script src="assets/style/js/ui.js?v=<?= time() ?>"></script>
    <script src="api/dashboard/dashboard.js?v=<?= time() ?>"></script>
    <script>
        window.DEFAULT_PERIODO = 'all';
    </script>
    <script src="api/dashboard/render.js?v=<?= time() ?>"></script>

    <?php if ($is_first_login): ?>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
        <script src="api/dashboard/tour.js?v=<?= time() ?>"></script>
    <?php endif; ?>

</body>

</html>