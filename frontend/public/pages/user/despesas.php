<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../login.php');
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
    <title>InvestAi — Minhas Despesas</title>
    <meta name="description" content="Registre e gerencie suas despesas financeiras com o InvestAi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../assets/style/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/animations.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/internal-pages.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/despesas.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/footer.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../../assets/style/css/chat.css?v=<?= time() ?>">
</head>

<body>

    <?php $nav_active = 'despesas'; include '../../components/navbar.php'; ?>

    <!-- ALERT MOVED OUTSIDE FOR Z-INDEX FIX -->
    <div id="despesa-alert" class="alert-message"></div>

    <div class="main-container">

        <!-- ===== HEADER ===== -->
        <div class="page-header fade-in-up">
            <h1><i class="bi bi-credit-card-2-back"></i>Minhas Despesas</h1>
            <p>Registre e acompanhe seus gastos mensais.</p>
        </div>

        <!-- ===== ALERT ===== -->

        <!-- ===== CARDS DE RESUMO ===== -->
        <div class="summary-cards">
            <div class="summary-card fade-in-up delay-1">
                <div class="label"><i class="bi bi-cash-stack me-1"></i>Total do Mês</div>
                <div class="value" id="total-mes">R$ 0,00</div>
            </div>
            <div class="summary-card fade-in-up delay-2">
                <div class="label"><i class="bi bi-arrow-repeat me-1"></i>Despesas Fixas</div>
                <div class="value" id="total-fixos">R$ 0,00</div>
            </div>
            <div class="summary-card fade-in-up delay-3">
                <div class="label"><i class="bi bi-list-ol me-1"></i>Registros</div>
                <div class="value neutral" id="total-registros">0</div>
            </div>
        </div>

        <!-- ===== FORM NOVA DESPESA ===== -->
        <div class="form-card fade-in-up delay-2">
            <h2><i class="bi bi-plus-circle"></i>Registrar Nova Despesa</h2>
            <form id="form-despesa">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">DESCRIÇÃO</label>
                        <input type="text" id="despesa-descricao" class="form-control"
                            placeholder="Ex: Aluguel, Mercado, Luz..." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CATEGORIA</label>
                        <div class="d-flex gap-2">
                            <select id="despesa-categoria" class="form-select" required>
                                <option value="">Carregando...</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" onclick="openCategoriasModal()"
                                title="Gerenciar Categorias">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">VALOR (R$)</label>
                        <input type="number" id="despesa-valor" class="form-control" placeholder="0,00" step="0.01"
                            min="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DATA</label>
                        <input type="date" id="despesa-data" class="form-control" required>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="despesa-fixo">
                        <label class="form-check-label" for="despesa-fixo">Despesa fixa (recorrente)</label>
                    </div>
                    <button type="submit" class="btn-submit" id="btn-submit">
                        <i class="bi bi-plus-lg me-1"></i>Registrar
                    </button>
                </div>
            </form>
        </div>

        <!-- ===== LISTA DE DESPESAS ===== -->
        <div class="list-container">
            <div class="list-header">
                <h2><i class="bi bi-journal-text"></i>Histórico de Despesas</h2>
                <span class="badge-count" id="badge-count">0</span>
            </div>
            <div id="despesas-container">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Nenhuma despesa registrada ainda. Comece adicionando acima!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL EDIÇÃO ===== -->
    <div class="modal-overlay" id="modal-edit">
        <div class="modal-card">
            <h2><i class="bi bi-pencil-square"></i>Editar Despesa</h2>
            <form id="form-edit">
                <input type="hidden" id="edit-id">
                <div class="mb-3">
                    <label class="form-label">DESCRIÇÃO</label>
                    <input type="text" id="edit-descricao" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CATEGORIA</label>
                    <div class="d-flex gap-2">
                        <select id="edit-categoria" class="form-select" required>
                            <option value="">Carregando...</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" onclick="openCategoriasModal()"
                            title="Gerenciar Categorias">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
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
                    <label class="form-check-label" for="edit-fixo">Despesa fixa (recorrente)</label>
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
            <h3>Excluir despesa?</h3>
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
    <script src="../../../api/utils/shared.js?v=<?= time() ?>"></script>
    <script src="../../../api/utils/nav.js?v=<?= time() ?>"></script>
    <script src="../../../api/categorias/read.js?v=<?= time() ?>"></script>
    <script src="../../../api/categorias/render.js?v=<?= time() ?>"></script>
    <script src="../../../api/categorias/create.js?v=<?= time() ?>"></script>
    <script src="../../../api/categorias/update.js?v=<?= time() ?>"></script>
    <script src="../../../api/categorias/delete.js?v=<?= time() ?>"></script>
    <script src="../../../assets/style/js/ui.js?v=<?= time() ?>"></script>
    <script src="../../../api/despesas/read.js?v=<?= time() ?>"></script>
    <script src="../../../api/despesas/render.js?v=<?= time() ?>"></script>
    <script src="../../../api/despesas/create.js?v=<?= time() ?>"></script>
    <script src="../../../api/despesas/update.js?v=<?= time() ?>"></script>
    <script src="../../../api/despesas/delete.js?v=<?= time() ?>"></script>

    <?php include '../../components/footer.php'; ?>
    <script src="../../../assets/style/js/legal-modals.js"></script>
    <script src="../../../api/chat/enviar.js?v=<?= time() ?>"></script>
    <script src="../../../api/chat/ui.js?v=<?= time() ?>"></script>

</body>

</html>