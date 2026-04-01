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

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="assets/style/css/variables.css">
    <link rel="stylesheet" href="assets/style/css/internal-pages.css">
    <link rel="stylesheet" href="assets/style/css/animations.css">

    <style>
        .navbar-custom {
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .card-value {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
        }

        .card-value .label {
            font-size: 0.9rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: block;
        }

        .card-value .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
        }

        .value.positive {
            color: #22c55e;
        }

        .value.negative {
            color: #ef4444;
        }

        .value.neutral {
            color: #6366f1;
        }

        .actions {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            min-width: 120px;
            padding: 12px 20px;
            border-radius: 10px;
            border: 1px solid #6366f1;
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }

        .btn-action:hover {
            background: rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            color: #ef4444;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .loading-spinner {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid #6366f1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
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
</div>

<script src="api/dashboard.js"></script>
<script>
// Carregar dados do dashboard
async function inicializar() {
    const json = await carregarDashboard();
    
    if (json.status === 'success') {
        // Dados do usuário
        document.getElementById('user-name').textContent = json.usuario.nome;
        document.getElementById('user-greeting').textContent = json.usuario.nome;

        // Dados financeiros
        document.getElementById('saldo-inicial').textContent = formatarMoeda(json.financeiro.saldo_inicial);
        document.getElementById('saldo-atual').textContent = formatarMoeda(json.financeiro.saldo_atual);
        document.getElementById('renda-mensal').textContent = formatarMoeda(json.financeiro.renda_mensal);
        document.getElementById('total-ganhos').textContent = formatarMoeda(json.financeiro.total_ganhos);
        document.getElementById('total-despesas').textContent = formatarMoeda(json.financeiro.total_despesas);
        document.getElementById('objetivo').textContent = json.financeiro.objetivo_financeiro;

        // Mostrar dados
        document.getElementById('loading').style.display = 'none';
        document.getElementById('content').style.display = 'block';
    } else {
        document.getElementById('loading').innerHTML = '<p class="text-danger">Erro ao carregar dados</p>';
    }
}

// Formatar para moeda brasileira
function formatarMoeda(valor) {
    const num = parseFloat(valor || 0);
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(num);
}

// Inicializar ao carregar página
inicializar();
</script>

</body>
</html>