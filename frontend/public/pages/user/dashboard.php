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
    <title>InvestAi — Dashboard</title>

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
    <link rel="stylesheet" href="../../../assets/style/css/sugestoes.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/chat.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/footer.css?v=<?= time() ?>">

    <?php if ($is_first_login): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css" />
        <link rel="stylesheet" href="../../../assets/style/css/tour.css?v=<?= time() ?>">
    <?php endif; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

</head>

<body>

    <?php $nav_active = 'dashboard'; include '../../components/navbar.php'; ?>

    <div class="main-container">

        <!-- ===== HEADER ===== -->
        <div class="page-header fade-in-up">
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



        </div>

        <!-- ===== SEÇÃO PLANEJAMENTO DE ORÇAMENTO ===== -->
        <div class="orcamento-section anim-on-scroll" id="orcamento-section">
            <div class="orcamento-header">
                <div>
                    <h2><i class="bi bi-pie-chart-fill"></i>Planejamento de Orçamento</h2>
                    <p class="orcamento-subtitle">Acompanhe quanto você gastou em cada categoria este mês.</p>
                </div>
                <button class="btn-orcamento" id="btn-abrir-orcamento" onclick="abrirModalOrcamento()">
                    <i class="bi bi-plus-lg me-1"></i>Definir Limite
                </button>
            </div>

            <!-- Cards de progresso por categoria -->
            <div class="orcamento-grid" id="orcamento-grid">
                <div class="orcamento-empty" id="orcamento-empty">
                    <i class="bi bi-bar-chart-steps"></i>
                    <p>Nenhum limite definido ainda.</p>
                    <span>Clique em <strong>Definir Limite</strong> para começar!</span>
                </div>
            </div>
        </div>

        <!-- ===== SEÇÃO SUGESTÕES DE ECONOMIA ===== -->
        <div id="sugestoes-container" class="anim-on-scroll"></div>

        </div><!-- /#content -->

    </div><!-- /.main-container -->

    <!-- ===== MODAL ORÇAMENTO ===== -->
    <div class="orcamento-overlay" id="orcamento-overlay">
        <div class="orcamento-modal">
            <div class="orcamento-modal-header">
                <div class="orcamento-modal-title">
                    <i class="bi bi-pie-chart-fill"></i>
                    <span>Definir Limite de Orçamento</span>
                </div>
                <button class="orcamento-close" onclick="fecharModalOrcamento()" title="Fechar">&times;</button>
            </div>

            <div class="orcamento-modal-body">
                <div class="orcamento-form-group">
                    <label for="orc-categoria">CATEGORIA DE DESPESA</label>
                    <div class="orcamento-select-wrap">
                        <i class="bi bi-tag"></i>
                        <select id="orc-categoria">
                            <option value="">Selecione uma categoria...</option>
                        </select>
                    </div>
                </div>

                <div class="orcamento-form-group">
                    <label for="orc-limite">LIMITE MENSAL (R$)</label>
                    <div class="orcamento-input-wrap">
                        <i class="bi bi-currency-dollar"></i>
                        <input type="number" id="orc-limite" placeholder="Ex: 500,00" min="0.01" step="0.01">
                    </div>
                    <span class="orcamento-hint">Valores zerados, negativos ou em texto não são aceitos.</span>
                </div>

                <div id="orc-alert" class="orc-alert" style="display:none;"></div>
            </div>

            <div class="orcamento-modal-footer">
                <button class="orc-btn-cancelar" onclick="fecharModalOrcamento()">Cancelar</button>
                <button class="orc-btn-salvar" id="orc-btn-salvar" onclick="salvarOrcamento()">
                    <i class="bi bi-check2-all me-1"></i>Salvar Limite
                </button>
            </div>
        </div>
    </div>

    <!-- ===== MODAL CONFIRMAR DELETE ORÇAMENTO ===== -->
    <div class="orcamento-overlay" id="orc-modal-delete">
        <div class="confirm-card">
            <div class="icon-danger"><i class="bi bi-trash3"></i></div>
            <h3>Excluir orçamento?</h3>
            <p>Esta ação não pode ser desfeita. O limite será removido permanentemente.</p>
            <input type="hidden" id="orc-delete-id">
            <input type="hidden" id="orc-delete-nome">
            <div class="d-flex gap-3 justify-content-center">
                <button class="btn-cancel" onclick="fecharModalDeleteOrcamento()">Cancelar</button>
                <button class="btn-danger" id="orc-btn-confirm-delete">
                    <i class="bi bi-trash3 me-1"></i>Excluir
                </button>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/scroll-animations.js?v=<?= time() ?>"></script>
    <script src="../../../api/utils/shared.js?v=<?= time() ?>"></script>
    <script src="../../../api/utils/nav.js?v=<?= time() ?>"></script>
    <script src="../../../assets/style/js/ui.js?v=<?= time() ?>"></script>
    <script src="../../../api/dashboard/dashboard.js?v=<?= time() ?>"></script>
    <script>
        window.DEFAULT_PERIODO = 'all';
    </script>
    <script src="../../../api/dashboard/render.js?v=<?= time() ?>"></script>
    <script src="../../../api/orcamento/read.js?v=<?= time() ?>"></script>
    <script src="../../../api/orcamento/render.js?v=<?= time() ?>"></script>
    <script src="../../../api/orcamento/create.js?v=<?= time() ?>"></script>
    <script src="../../../api/orcamento/update.js?v=<?= time() ?>"></script>
    <script src="../../../api/orcamento/delete.js?v=<?= time() ?>"></script>
    <script src="../../../api/sugestoes/render.js?v=<?= time() ?>"></script>
    <script src="../../../api/sugestoes/regenerar.js?v=<?= time() ?>"></script>
    <script src="../../../api/sugestoes/read.js?v=<?= time() ?>"></script>
    <script src="../../../api/chat/enviar.js?v=<?= time() ?>"></script>
    <script src="../../../api/chat/ui.js?v=<?= time() ?>"></script>

    <?php if ($is_first_login): ?>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
        <script src="../../../api/dashboard/tour.js?v=<?= time() ?>"></script>
    <?php endif; ?>

    <?php include '../../components/footer.php'; ?>
    <script src="../../../assets/style/js/legal-modals.js"></script>

</body>

</html>