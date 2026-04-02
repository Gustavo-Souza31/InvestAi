<?php
session_start();

// Se já está logado, ir para dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestAi — Inteligência Financeira</title>
    <meta name="description" content="Gerencie suas finanças com precisão e inteligência usando IA">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style/css/variables.css">
    <link rel="stylesheet" href="assets/style/css/animations.css">
    <link rel="stylesheet" href="assets/style/css/navbar.css">
    <link rel="stylesheet" href="assets/style/css/internal-pages.css">
    <link rel="stylesheet" href="assets/style/css/dashboard.css">

</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-custom">
        <div class="container d-flex align-items-center justify-content-between" style="max-width:960px;">
            <a href="index.php" class="logo"><i class="bi bi-graph-up-arrow me-1"></i>Invest<span>Ai</span></a>
            <div class="d-flex align-items-center gap-4">
                <span class="nav-link-custom active">Dashboard</span>
                <a href="login.php" class="nav-link-custom nav-ganhos" style="text-decoration: none; color: inherit; cursor: pointer;">Ganhos</a>
                <a href="login.php" class="nav-link-custom nav-despesas" style="text-decoration: none; color: inherit; cursor: pointer;">Despesas</a>
                <a href="login.php" class="user-badge" style="background: #a855f7; border: 1px solid #a855f7; text-decoration: none; color: inherit; cursor: pointer;">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                </a>
            </div>
        </div>
    </nav>

    <div class="main-container">

        <!-- ===== HEADER ===== -->
        <div class="page-header">
            <h1><i class="bi bi-speedometer2"></i>Dashboard</h1>
            <p>Bem-vindo! Veja como funciona a plataforma.</p>
        </div>

        <!-- ===== CARDS DE RESUMO ===== -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="label"><i class="bi bi-wallet2 me-1"></i>Saldo Atual</div>
                <div class="value">R$ 12.450,00</div>
            </div>
            <div class="summary-card">
                <div class="label"><i class="bi bi-percent me-1"></i>Saldo Inicial</div>
                <div class="value">R$ 8.000,00</div>
            </div>
            <div class="summary-card">
                <div class="label"><i class="bi bi-arrow-up-right me-1"></i>Total Ganhos</div>
                <div class="value">R$ 8.500,00</div>
            </div>
            <div class="summary-card">
                <div class="label"><i class="bi bi-arrow-down-left me-1"></i>Total Despesas</div>
                <div class="value">R$ 4.050,00</div>
            </div>
            <div class="summary-card">
                <div class="label"><i class="bi bi-cash-stack me-1"></i>Renda Mensal</div>
                <div class="value">R$ 5.500,00</div>
            </div>
            <div class="summary-card">
                <div class="label"><i class="bi bi-target me-1"></i>Objetivo Financeiro</div>
                <div class="value">Viagem Japão</div>
            </div>
        </div>

        <!-- ===== INFO MESSAGE ===== -->
        <div style="text-align: center; margin-top: 50px; padding: 30px; background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 16px;">
            <i class="bi bi-info-circle" style="color: #a855f7; font-size: 1.8rem; display: block; margin-bottom: 12px;"></i>
            <h3 style="font-size: 1.1rem; margin-bottom: 8px;">Isso é uma demonstração</h3>
            <p style="color: #666; margin: 0; font-size: 0.9rem;">
                Clique em "Entrar" para criar sua conta e começar a gerenciar suas finanças com inteligência.
            </p>
        </div>

    </div>

</body>

</html>
