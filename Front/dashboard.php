<?php
session_start();

// Redirecionar se não logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
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
    <link rel="stylesheet" href="assets/style/css/variables.css">
    <link rel="stylesheet" href="assets/style/css/animations.css">
    <link rel="stylesheet" href="assets/style/css/internal-pages.css">
    <link rel="stylesheet" href="assets/style/css/dashboard.css">


</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand">
            <i class="bi bi-graph-up-arrow me-2"></i><strong>Invest<span style="color:#6366f1;">Ai</span></strong>
        </a>
        <div class="ms-auto">
            <span class="text-secondary me-3" id="user-name">Carregando...</span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
        <p class="text-secondary">Carregando dados...</p>
    </div>

    <div id="content" style="display: none;">
        <h2 class="mb-4">Bem-vindo(a) de volta, <span id="user-greeting" class="text-gradient"></span>!</h2>

        <div class="dashboard-grid">
            <div class="card-value">
                <span class="label">Saldo Atual</span>
                <span class="value neutral" id="saldo-atual">R$ 0,00</span>
            </div>
            <div class="card-value">
                <span class="label">Saldo Inicial</span>
                <span class="value neutral" id="saldo-inicial">R$ 0,00</span>
            </div>
            <div class="card-value">
                <span class="label">Renda Mensal</span>
                <span class="value positive" id="renda-mensal">R$ 0,00</span>
            </div>
            <div class="card-value">
                <span class="label">Total de Ganhos</span>
                <span class="value positive" id="total-ganhos">R$ 0,00</span>
            </div>
            <div class="card-value">
                <span class="label">Total de Despesas</span>
                <span class="value negative" id="total-despesas">R$ 0,00</span>
            </div>
            <div class="card-value">
                <span class="label">Objetivo Financeiro</span>
                <p class="text-secondary" id="objetivo" style="margin: 0;">Não definido</p>
            </div>
        </div>

        <div class="actions">
            <a href="ganhos.php" class="btn-action">
                <i class="bi bi-plus-circle me-2"></i> Adicionar Ganho
            </a>
            <a href="despesas.php" class="btn-action">
                <i class="bi bi-plus-circle me-2"></i> Adicionar Despesa
            </a>
            <a href="logout.php" class="btn-action btn-logout">
                <i class="bi bi-box-arrow-left me-2"></i> Sair
            </a>
        </div>
    </div>
</div>pi/shared.js"></script>
<script src="assets/style/js/navbar.js"></script>
<script src="a

<script src="api/shared.js"></script>
<script src="assets/style/js/ui.js"></script>
<script src="api/dashboard/dashboard.js"></script>
<script src="api/dashboard/render.js"></script>

</body>
</html>