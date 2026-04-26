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
    <title>InvestAi — Resumo Financeiro</title>

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
                <a href="dashboard.php" class="nav-link-custom">Dashboard</a>
                <a href="resumo.php" class="nav-link-custom active nav-resumo">Resumo Financeiro</a>
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
            <h1><i class="bi bi-pie-chart"></i>Resumo Financeiro</h1>
            <p>Análise detalhada da sua evolução financeira por período.</p>
        </div>

        <!-- ===== LOADING STATE ===== -->
        <div id="loading" class="loading">
            <div class="loading-spinner"></div>
            <p class="text-secondary">Carregando dados...</p>
        </div>

        <!-- ===== CONTENT ===== -->
        <div id="content" style="display: none;">


            <!-- ===== GRÁFICOS DE RELATÓRIO ===== -->
            <div class="charts-section">
                <div class="charts-section-header">
                    <h2><i class="bi bi-bar-chart-line"></i>Relatório Financeiro</h2>
                    <div class="chart-filters">
                        <button class="chart-filter-btn" data-periodo="1m">Mensal</button>
                        <button class="chart-filter-btn active" data-periodo="3m">Trimestral</button>
                        <button class="chart-filter-btn" data-periodo="6m">Semestral</button>
                        <button class="chart-filter-btn" data-periodo="1a">Anual</button>
                    </div>
                </div>

                <!-- Nova seção de comparativos do Resumo Financeiro -->
                <div class="comparative-summary d-flex flex-wrap gap-3 mb-4" id="comparative-summary-container">
                    <div class="comp-box" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); padding: 15px 20px; border-radius: 12px; flex: 1; min-width: 280px;">
                        <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Ganhos no Período</span>
                        <div class="d-flex align-items-center gap-3 mt-1">
                            <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);" id="total-ganhos">R$ 0,00</span>
                            <div id="badge-ganhos" class="comparison-badge" style="font-size: 0.85rem; font-weight: 600; padding: 4px 8px; border-radius: 6px; background: rgba(0,0,0,0.2);"></div>
                        </div>
                    </div>
                    <div class="comp-box" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); padding: 15px 20px; border-radius: 12px; flex: 1; min-width: 280px;">
                        <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Despesas no Período</span>
                        <div class="d-flex align-items-center gap-3 mt-1">
                            <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);" id="total-despesas">R$ 0,00</span>
                            <div id="badge-despesas" class="comparison-badge" style="font-size: 0.85rem; font-weight: 600; padding: 4px 8px; border-radius: 6px; background: rgba(0,0,0,0.2);"></div>
                        </div>
                    </div>
                </div>

                <div class="charts-grid">
                    <!-- Loading -->
                    <div class="charts-loading" id="charts-loading">
                        <div style="text-align:center;">
                            <div class="loading-spinner"></div>
                            <p class="text-secondary">Carregando gráficos...</p>
                        </div>
                    </div>

                    <!-- Conteúdo dos gráficos -->
                    <div id="charts-content"
                        style="display:none; grid-column: 1 / -1; grid-template-columns: 1.6fr 1fr; gap: 20px;">
                        <!-- Gráfico de Linha -->
                        <div class="chart-container">
                            <h3><i class="bi bi-graph-up"></i> Evolução Mensal</h3>
                            <div class="chart-canvas-wrapper">
                                <canvas id="grafico-linha"></canvas>
                            </div>
                        </div>

                        <!-- Gráfico de Rosca -->
                        <div class="chart-container">
                            <h3><i class="bi bi-pie-chart"></i> Proporção</h3>
                            <div class="chart-doughnut-wrapper">
                                <div class="chart-doughnut-canvas">
                                    <canvas id="grafico-rosca"></canvas>
                                </div>

                                <div class="chart-legend">
                                    <div class="chart-legend-item">
                                        <div class="legend-left">
                                            <span class="legend-dot ganhos"></span>
                                            <span class="legend-label">Ganhos</span>
                                        </div>
                                        <div class="legend-right">
                                            <span class="legend-value" id="legenda-ganhos-valor">R$ 0,00</span>
                                            <span class="legend-perc" id="legenda-ganhos-perc">0%</span>
                                        </div>
                                    </div>
                                    <div class="chart-legend-item">
                                        <div class="legend-left">
                                            <span class="legend-dot despesas"></span>
                                            <span class="legend-label">Despesas</span>
                                        </div>
                                        <div class="legend-right">
                                            <span class="legend-value" id="legenda-despesas-valor">R$ 0,00</span>
                                            <span class="legend-perc" id="legenda-despesas-perc">0%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="chart-saldo-wrapper">
                                    <div class="chart-saldo-label">Saldo do Período</div>
                                    <div class="chart-saldo positivo" id="saldo-periodo">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script src="api/utils/shared.js?v=<?= time() ?>"></script>
    <script src="api/utils/nav.js?v=<?= time() ?>"></script>
    <script src="assets/style/js/ui.js?v=<?= time() ?>"></script>
    <script src="api/dashboard/dashboard.js?v=<?= time() ?>"></script>
    <script src="api/dashboard/charts.js?v=<?= time() ?>"></script>
    <script src="api/dashboard/render.js?v=<?= time() ?>"></script>

    <?php if ($is_first_login): ?>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
        <script src="api/dashboard/tour.js"></script>
    <?php endif; ?>

</body>

</html>