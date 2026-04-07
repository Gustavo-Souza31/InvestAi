<?php
session_start();

// Redirecionar se não logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$nome = htmlspecialchars($_SESSION['usuario_nome']);
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
    <link rel="stylesheet" href="assets/style/css/variables.css">
    <link rel="stylesheet" href="assets/style/css/animations.css">
    <link rel="stylesheet" href="assets/style/css/navbar.css">
    <link rel="stylesheet" href="assets/style/css/internal-pages.css">
    <link rel="stylesheet" href="assets/style/css/dashboard.css?v=<?= time() ?>">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-custom">
        <div class="container d-flex align-items-center justify-content-between" style="max-width:960px;">
            <a href="dashboard.php" class="logo"><i class="bi bi-graph-up-arrow me-1"></i>Invest<span>Ai</span></a>
            <div class="d-flex align-items-center gap-4">
                <a href="dashboard.php" class="nav-link-custom active">Dashboard</a>
                <a href="ganhos.php" class="nav-link-custom nav-ganhos">Ganhos</a>
                <a href="despesas.php" class="nav-link-custom nav-despesas">Despesas</a>
                <span class="user-badge"><i class="bi bi-person-fill me-1"></i><?= $nome ?></span>
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

            <!-- ===== GRÁFICOS DE RELATÓRIO ===== -->
            <div class="charts-section">
                <div class="charts-section-header">
                    <h2><i class="bi bi-bar-chart-line"></i>Relatório Financeiro</h2>
                    <div class="chart-filters">
                        <button class="chart-filter-btn" data-periodo="1s">1 Semana</button>
                        <button class="chart-filter-btn active" data-periodo="3m">3 Meses</button>
                        <button class="chart-filter-btn" data-periodo="6m">6 Meses</button>
                        <button class="chart-filter-btn" data-periodo="1a">1 Ano</button>
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
                    <div id="charts-content" style="display:none; grid-column: 1 / -1; grid-template-columns: 1.6fr 1fr; gap: 20px;">
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

    <script src="api/utils/shared.js"></script>
    <script src="api/dashboard/dashboard.js"></script>
    <script src="api/dashboard/render.js"></script>
    <script src="api/dashboard/charts.js"></script>

</body>

</html>