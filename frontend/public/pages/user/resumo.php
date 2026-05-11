<?php
session_start();

// Redirecionar se não logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../login.php');
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
    <link rel="stylesheet" href="../../../assets/style/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/animations.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/internal-pages.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/footer.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/chat.css?v=<?= time() ?>">

    <?php if ($is_first_login): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css" />
    <?php endif; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

</head>

<body>

    <?php $nav_active = 'resumo'; include '../../components/navbar.php'; ?>

    <div class="main-container">

        <!-- ===== HEADER ===== -->
        <div class="page-header fade-in-up">
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

            <!-- ===== CARDS DE RESUMO ===== -->
            <div class="summary-cards">
                <div class="summary-card fade-in-up delay-1">
                    <div class="label"><i class="bi bi-wallet2 me-1"></i>Saldo Atual</div>
                    <div class="value" id="saldo-atual">R$ 0,00</div>
                </div>
                <div class="summary-card fade-in-up delay-2">
                    <div class="label"><i class="bi bi-percent me-1"></i>Saldo Inicial</div>
                    <div class="value" id="saldo-inicial">R$ 0,00</div>
                </div>
                <div class="summary-card fade-in-up delay-1">
                    <div class="label"><i class="bi bi-arrow-up-right me-1"></i>Total Ganhos</div>
                    <div class="value" id="total-ganhos">R$ 0,00</div>
                </div>
                <div class="summary-card fade-in-up delay-2">
                    <div class="label"><i class="bi bi-arrow-down-left me-1"></i>Total Despesas</div>
                    <div class="value" id="total-despesas">R$ 0,00</div>
                </div>
                <div class="summary-card fade-in-up delay-1">
                    <div class="label"><i class="bi bi-cash-stack me-1"></i>Renda Mensal</div>
                    <div class="value" id="renda-mensal">R$ 0,00</div>
                </div>
                <div class="summary-card fade-in-up delay-2">
                    <div class="label"><i class="bi bi-target me-1"></i>Objetivo Financeiro</div>
                    <div class="value" id="objetivo">Não definido</div>
                </div>
            </div>

            <!-- ===== GRÁFICOS DE RELATÓRIO ===== -->
            <div class="charts-section anim-on-scroll">
                <div class="charts-section-header" style="flex-wrap: wrap; gap: 15px;">
                    <h2><i class="bi bi-bar-chart-line"></i>Relatório Financeiro</h2>
                    <div class="d-flex flex-column align-items-end gap-2">
                        <div class="chart-filters">
                            <button class="chart-filter-btn active" data-periodo="1m">Mensal</button>
                            <button class="chart-filter-btn" data-periodo="3m">Trimestral</button>
                            <button class="chart-filter-btn" data-periodo="6m">Semestral</button>
                            <button class="chart-filter-btn" data-periodo="1a">Anual</button>
                        </div>
                        <div class="d-flex gap-2 align-items-center mt-1">
                            <div class="d-flex gap-2" id="container-intervalos" style="display: none;">
                                <select id="select-intervalo" class="form-select form-select-sm" style="background-color: var(--bg-dark); color: var(--text-main); border-color: rgba(255,255,255,0.1); width: auto;">
                                </select>
                                <select id="select-ano" class="form-select form-select-sm" style="background-color: var(--bg-dark); color: var(--text-main); border-color: rgba(255,255,255,0.1); width: auto;">
                                </select>
                            </div>
                            <select id="filtro-categoria-dashboard" class="form-select form-select-sm" style="background-color: var(--bg-dark); color: var(--text-main); border-color: rgba(255,255,255,0.1); width: auto; min-width: 200px;">
                                <option value="">Resumo Geral (Ganhos vs Despesas)</option>
                                <option value="todas">Visão por Categorias (Apenas Despesas)</option>
                            </select>
                            <select id="tipo-comparacao" class="form-select form-select-sm" style="background-color: var(--bg-dark); color: var(--text-main); border-color: rgba(255,255,255,0.1); width: auto;">
                                <option value="yoy">Comparar: Ano a Ano</option>
                                <option value="consecutivo">Comparar: Período Anterior</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Nova seção de comparativos do Resumo Financeiro -->
                <div class="comparative-summary d-flex flex-wrap gap-3 mb-4" id="comparative-summary-container">
                    <div class="comp-box" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); padding: 15px 20px; border-radius: 12px; flex: 1; min-width: 280px;">
                        <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Ganhos no Período</span>
                        <div class="d-flex align-items-center gap-3 mt-1">
                            <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);" id="comp-total-ganhos">R$ 0,00</span>
                            <div id="badge-ganhos" class="comparison-badge" style="font-size: 0.85rem; font-weight: 600; padding: 4px 8px; border-radius: 6px; background: rgba(0,0,0,0.2);"></div>
                        </div>
                    </div>
                    <div class="comp-box" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); padding: 15px 20px; border-radius: 12px; flex: 1; min-width: 280px;">
                        <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Despesas no Período</span>
                        <div class="d-flex align-items-center gap-3 mt-1">
                            <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);" id="comp-total-despesas">R$ 0,00</span>
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
                        <!-- Gráfico de Linha ou Lista de Categorias -->
                        <div class="chart-container">
                            <h3 id="linha-titulo"><i class="bi bi-graph-up"></i> Evolução Mensal</h3>
                            <div class="chart-canvas-wrapper" id="linha-wrapper-geral">
                                <canvas id="grafico-linha"></canvas>
                            </div>
                            
                            <!-- Lista de Categorias (oculta por padrão) -->
                            <div id="linha-wrapper-cat" style="display: none; height: 100%; min-height: 300px; max-height: 400px; overflow-y: auto; padding-right: 10px;">
                                <div id="cat-lista-container"></div>
                            </div>
                        </div>

                        <!-- Gráfico de Rosca -->
                        <div class="chart-container">
                            <h3 id="rosca-titulo"><i class="bi bi-pie-chart"></i> Proporção</h3>
                            <div class="chart-doughnut-wrapper" id="rosca-wrapper-geral">
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
                            
                            <!-- Wrapper alternativo para rosca de categorias -->
                            <div id="rosca-wrapper-cat" style="display: none; width: 100%; position: relative; margin-top: 20px;">
                                <div class="chart-doughnut-canvas" style="height: 300px;">
                                    <canvas id="grafico-categoria"></canvas>
                                </div>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                                    <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Total</div>
                                    <div id="cat-total-geral" style="font-size: 1.1rem; font-weight: 700; color: var(--text-main);">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



        </div>

    </div>

    <script src="../../../assets/js/scroll-animations.js?v=<?= time() ?>"></script>
    <script src="../../../api/utils/shared.js?v=<?= time() ?>"></script>
    <script src="../../../api/utils/nav.js?v=<?= time() ?>"></script>
    <script src="../../../assets/style/js/ui.js?v=<?= time() ?>"></script>
    <script src="../../../api/dashboard/dashboard.js?v=<?= time() ?>"></script>
    <script src="../../../api/dashboard/charts.js?v=<?= time() ?>"></script>
    <script src="../../../api/dashboard/render.js?v=<?= time() ?>"></script>

    <?php if ($is_first_login): ?>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
        <script src="../../../api/dashboard/tour.js"></script>
    <?php endif; ?>

    <?php include '../../components/footer.php'; ?>
    <script src="../../../assets/style/js/legal-modals.js"></script>
    <script src="../../../api/chat/enviar.js?v=<?= time() ?>"></script>
    <script src="../../../api/chat/ui.js?v=<?= time() ?>"></script>

</body>

</html>