<?php
session_start();

// Redirecionar para dashboard se já logado
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
    <title>InvestAi — Entrar ou Cadastrar</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/style/css/variables.css">
    <link rel="stylesheet" href="assets/style/css/navbar.css">
    <link rel="stylesheet" href="assets/style/css/auth.css">
    <link rel="stylesheet" href="assets/style/css/animations.css">
</head>
<body>

<div class="auth-card">
    <!-- Logo -->
    <div class="text-center mb-5">
        <a href="index.php" class="logo"><i class="bi bi-graph-up-arrow me-1"></i>Invest<span>Ai</span></a>
        <p class="text-secondary mt-2 mb-0" style="font-size:0.88rem;">Sua inteligência financeira pessoal</p>
    </div>

    <!-- Abas -->
    <div class="auth-tabs">
        <button class="auth-tab active" id="tab-login">Entrar</button>
        <button class="auth-tab" id="tab-cadastro">Criar Conta</button>
    </div>

    <!-- Alert de feedback -->
    <div id="auth-alert" class="auth-alert"></div>

    <!-- ===== FORM LOGIN ===== -->
    <form id="form-login">
        <div class="mb-3">
            <label class="form-label">E-MAIL</label>
            <div class="input-icon">
                <input type="email" id="login-email" class="form-control" placeholder="seu@email.com" required>
                <i class="bi bi-envelope"></i>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">SENHA</label>
            <div class="input-icon">
                <input type="password" id="login-senha" class="form-control" placeholder="••••••••" required>
                <i class="bi bi-lock"></i>
            </div>
        </div>
        <button type="submit" class="btn-auth">
            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
        </button>
        <p class="text-center mt-4 mb-0" style="font-size:0.85rem; color:#666;">
            Não tem conta? <a href="#" id="link-to-cadastro" style="color:#6366f1;">Cadastre-se aqui</a>
        </p>
    </form>

    <!-- ===== FORM CADASTRO ===== -->
    <form id="form-cadastro" style="display:none;">
        <div class="mb-3">
            <label class="form-label">NOME COMPLETO</label>
            <div class="input-icon">
                <input type="text" id="cadastro-nome" class="form-control" placeholder="Seu nome" required>
                <i class="bi bi-person"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">E-MAIL</label>
            <div class="input-icon">
                <input type="email" id="cadastro-email" class="form-control" placeholder="seu@email.com" required>
                <i class="bi bi-envelope"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">CPF</label>
            <div class="input-icon">
                <input type="text" id="cadastro-cpf" class="form-control" placeholder="000.000.000-00" maxlength="14" required>
                <i class="bi bi-card-text"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">TELEFONE</label>
            <div class="input-icon">
                <input type="tel" id="cadastro-telefone" class="form-control" placeholder="(11) 99999-9999" maxlength="14" required>
                <i class="bi bi-telephone"></i>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">SENHA</label>
            <div class="input-icon">
                <input type="password" id="cadastro-senha" class="form-control" placeholder="••••••••" required>
                <i class="bi bi-lock"></i>
            </div>
        </div>
        <button type="submit" class="btn-auth">
            <i class="bi bi-plus-circle me-2"></i>Criar Conta
        </button>
        <p class="text-center mt-4 mb-0" style="font-size:0.85rem; color:#666;">
            Já tem conta? <a href="#" id="link-to-login" style="color:#6366f1;">Faça login</a>
        </p>
    </form>
</div>

<script src="api/utils/shared.js"></script>
<script src="assets/style/js/ui.js"></script>
<script src="api/auth/authUI.js"></script>
<script src="api/auth/login.js"></script>
<script src="api/auth/cadastro.js"></script>

</body>
</html>
