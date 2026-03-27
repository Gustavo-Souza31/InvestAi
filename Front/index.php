<?php
require_once '../db/conexao.php';

// Buscar o nome do usuário para teste de conexão
$usuario_nome = 'Visitante';
$result = $conexao->query("SELECT nome FROM usuarios LIMIT 1");
if ($result && $result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
    $usuario_nome = $usuario['nome'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestAi - Inteligência Financeira</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top glass-nav">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="#">
                <i class="bi bi-graph-up-arrow text-primary me-2"></i>Invest<span class="text-primary">Ai</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3"><span class="text-light fw-semibold">Bem-vindo, <?php echo htmlspecialchars($usuario_nome); ?>!</span></li>
                    <li class="nav-item"><a class="nav-link" href="#dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="#metas">Minhas Metas</a></li>
                    <li class="nav-item"><a class="nav-link" href="#ia">Sugestões IA</a></li>
                    <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                        <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">Entrar</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section d-flex align-items-center">
        <div class="container text-center text-lg-start position-relative z-1">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-3 border border-primary border-opacity-25 fade-in-up">
                        <i class="bi bi-robot me-1"></i> Inteligência Artificial Integrada
                    </div>
                    <h1 class="display-4 fw-bold mb-4 fade-in-up delay-1">
                        Gerencie Suas Finanças com <span class="text-gradient">Precisão e Inteligência</span>
                    </h1>
                    <p class="lead text-secondary mb-5 fade-in-up delay-2">
                        Utilize comandos de voz para cadastrar despesas, receba sugestões de investimentos em tempo real e atinja suas metas de forma rápida e segura.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start fade-in-up delay-3">
                        <button id="btnVoiceCommand" class="btn btn-primary btn-lg rounded-pill d-flex align-items-center justify-content-center gap-2 glow-effect">
                            <i class="bi bi-mic-fill fs-5"></i> Falar Despesa
                        </button>
                        <button class="btn btn-outline-light btn-lg rounded-pill px-4">
                            Ver Dashboard
                        </button>
                    </div>
                </div>
                <div class="col-lg-6 position-relative fade-in-up delay-2">
                    <!-- Glassmorphism Card Element to simulate Dashboard -->
                    <div class="glass-card p-4 rounded-4 shadow-lg position-relative">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0 fw-semibold">Balanço Atual</h5>
                            <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3">+ 14% este mês</span>
                        </div>
                        <h2 class="display-5 fw-bold mb-4">R$ 12.450<span class="fs-4 text-secondary">,00</span></h2>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-sm text-secondary mb-1">
                                <span>Meta: Viagem Japão</span>
                                <span>65%</span>
                            </div>
                            <div class="progress bg-dark-subtle" style="height: 8px;">
                                <div class="progress-bar bg-primary rounded-pill progress-animation" style="width: 65%;"></div>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25 d-flex align-items-start gap-3">
                            <div class="bg-primary bg-opacity-25 p-2 rounded-circle text-primary">
                                <i class="bi bi-lightbulb-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-semibold mb-1">Dica InvestAi</h6>
                                <p class="text-secondary text-sm mb-0">Baseado no seu perfil, investir R$ 500 no Tesouro Direto hoje pode acelerar sua meta em 2 meses.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Background Gradients -->
        <div class="hero-bg-gradient-1"></div>
        <div class="hero-bg-gradient-2"></div>
    </header>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
