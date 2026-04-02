<?php
session_start();
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
    <title>InvestAi — Meus Ganhos</title>
    <meta name="description" content="Registre e gerencie seus ganhos financeiros com o InvestAi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style/css/variables.css">
    <link rel="stylesheet" href="assets/style/css/animations.css">
    <link rel="stylesheet" href="assets/style/css/internal-pages.css">
    <link rel="stylesheet" href="assets/style/css/ganhos.css">
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-custom">
        <div class="container d-flex align-items-center justify-content-between" style="max-width:960px;">
            <a href="dashboard.php" class="logo"><i class="bi bi-graph-up-arrow me-1"></i>Invest<span>Ai</span></a>
            <div class="d-flex align-items-center gap-4">
                <a href="dashboard.php" class="nav-link-custom">Dashboard</a>
                <a href="ganhos.php" class="nav-link-custom active">Ganhos</a>
                <a href="despesas.php" class="nav-link-custom">Despesas</a>
                <span class="user-badge"><i class="bi bi-person-fill me-1"></i><?= $nome ?></span>
                <a href="logout.php" class="nav-link-custom" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </nav>

    <div class="main-container">

        <!-- ===== HEADER ===== -->
        <div class="page-header">
            <h1><i class="bi bi-wallet2"></i>Meus Ganhos</h1>
            <p>Registre e acompanhe suas fontes de renda.</p>
        </div>

        <!-- ===== ALERT ===== -->
        <div id="ganho-alert" class="alert-message"></div>

        <!-- ===== CARDS DE RESUMO ===== -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="label"><i class="bi bi-cash-stack me-1"></i>Total do Mês</div>
                <div class="value" id="total-mes">R$ 0,00</div>
            </div>
            <div class="summary-card">
                <div class="label"><i class="bi bi-arrow-repeat me-1"></i>Ganhos Fixos</div>
                <div class="value" id="total-fixos">R$ 0,00</div>
            </div>
            <div class="summary-card">
                <div class="label"><i class="bi bi-list-ol me-1"></i>Registros</div>
                <div class="value neutral" id="total-registros">0</div>
            </div>
        </div>

        <!-- ===== FORM NOVO GANHO ===== -->
        <div class="form-card">
            <h2><i class="bi bi-plus-circle"></i>Registrar Novo Ganho</h2>
            <form id="form-ganho">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">DESCRIÇÃO</label>
                        <input type="text" id="ganho-descricao" class="form-control"
                            placeholder="Ex: Salário, Freelance, Venda..." required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">VALOR (R$)</label>
                        <input type="number" id="ganho-valor" class="form-control" placeholder="0,00" step="0.01"
                            min="0.01" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">DATA</label>
                        <input type="date" id="ganho-data" class="form-control" required>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="ganho-fixo">
                        <label class="form-check-label" for="ganho-fixo">Ganho fixo (recorrente)</label>
                    </div>
                    <button type="submit" class="btn-submit" id="btn-submit">
                        <i class="bi bi-plus-lg me-1"></i>Registrar
                    </button>
                </div>
            </form>
        </div>

        <!-- ===== LISTA DE GANHOS ===== -->
        <div class="list-container">
            <div class="list-header">
                <h2><i class="bi bi-journal-text"></i>Histórico de Ganhos</h2>
                <span class="badge-count" id="badge-count">0</span>
            </div>
            <div id="ganhos-container">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Nenhum ganho registrado ainda. Comece adicionando acima!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL EDIÇÃO ===== -->
    <div class="modal-overlay" id="modal-edit">
        <div class="modal-card">
            <h2><i class="bi bi-pencil-square"></i>Editar Ganho</h2>
            <form id="form-edit">
                <input type="hidden" id="edit-id">
                <div class="mb-3">
                    <label class="form-label">DESCRIÇÃO</label>
                    <input type="text" id="edit-descricao" class="form-control" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">VALOR (R$)</label>
                        <input type="number" id="edit-valor" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">DATA</label>
                        <input type="date" id="edit-data" class="form-control" required>
                    </div>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="edit-fixo">
                    <label class="form-check-label" for="edit-fixo">Ganho fixo (recorrente)</label>
                </div>
                <div class="d-flex gap-3 justify-content-end">
                    <button type="button" class="btn-cancel" onclick="closeModal('modal-edit')">Cancelar</button>
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check-lg me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL EXCLUIR ===== -->
    <div class="modal-overlay" id="modal-delete">
        <div class="confirm-card">
            <div class="icon-danger"><i class="bi bi-trash3"></i></div>
            <h3>Excluir ganho?</h3>
            <p>Esta ação não pode ser desfeita. O registro será removido permanentemente.</p>
            <input type="hidden" id="delete-id">
            <div class="d-flex gap-3 justify-content-center">
                <button class="btn-cancel" onclick="closeModal('modal-delete')">Cancelar</button>
                <button class="btn-danger" id="btn-confirm-delete">
                    <i class="bi bi-trash3 me-1"></i>Excluir
                </button>
            </div>
        </div>
    </div>

    <!-- ===== SCRIPTS ===== -->
    <script>
        const USUARIO_ID = <?= $usuario_id ?>;
    </script>
    <script src="api/shared.js"></script>
    <script src="assets/style/js/ui.js"></script>
    <script src="api/ganhos/read.js"></script>
    <script src="api/ganhos/render.js"></script>
    <script src="api/ganhos/create.js"></script>
    <script src="api/ganhos/update.js"></script>
    <script src="api/ganhos/delete.js"></script>

</body>

</html>