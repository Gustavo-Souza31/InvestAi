<?php
session_start();
// Se já está logado, vai direto pro dashboard
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: #0d0f14;
            color: #e0e0e0;
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        /* Gradientes de fundo */
        body::before {
            content: '';
            position: fixed;
            top: -200px; left: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(99,102,241,0.18) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -200px; right: -200px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(6,182,212,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 20px;
            padding: 40px 36px;
            width: 100%;
            max-width: 440px;
            backdrop-filter: blur(12px);
            position: relative;
            z-index: 1;
        }

        .logo { font-size: 1.6rem; font-weight: 700; text-decoration: none; color: #fff; }
        .logo span { color: #6366f1; }

        /* Tabs customizadas */
        .auth-tabs { display: flex; gap: 4px; background: rgba(255,255,255,0.06); border-radius: 10px; padding: 4px; margin-bottom: 28px; }
        .auth-tab {
            flex: 1; text-align: center; padding: 9px;
            border-radius: 8px; cursor: pointer; font-weight: 600;
            font-size: 0.9rem; color: #888; transition: all 0.25s;
            border: none; background: transparent;
        }
        .auth-tab.active { background: #6366f1; color: #fff; }

        /* Inputs */
        .form-control {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: #e0e0e0 !important;
            border-radius: 10px !important;
            padding: 12px 14px !important;
            font-family: 'Outfit', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important;
            background: rgba(99,102,241,0.06) !important;
        }
        .form-control::placeholder { color: #555 !important; }
        .form-label { color: #aaa; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.03em; }

        /* Botão principal */
        .btn-auth {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none; border-radius: 10px;
            color: #fff; font-weight: 700; font-size: 1rem;
            font-family: 'Outfit', sans-serif;
            cursor: pointer; transition: opacity 0.2s, transform 0.15s;
        }
        .btn-auth:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-auth:active { transform: translateY(0); }
        .btn-auth:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* Alert de feedback */
        .auth-alert {
            border-radius: 10px; font-size: 0.88rem;
            padding: 11px 14px; margin-bottom: 18px; display: none;
        }
        .auth-alert.error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
        .auth-alert.success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }

        .input-icon { position: relative; }
        .input-icon i { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #555; font-size: 1rem; pointer-events: none; }
    </style>
</head>
<body>

<div class="auth-card">
    <!-- Logo -->
    <div class="text-center mb-5">
        <a href="index.php" class="logo"><i class="bi bi-graph-up-arrow me-1"></i>Invest<span>Ai</span></a>
        <p class="text-secondary mt-2 mb-0" style="font-size:0.88rem;">Sua inteligência financeira pessoal</p>
    </div>

    <!-- Tabs -->
    <div class="auth-tabs">
        <button class="auth-tab active" id="tab-login" onclick="switchTab('login')">Entrar</button>
        <button class="auth-tab" id="tab-cadastro" onclick="switchTab('cadastro')">Criar Conta</button>
    </div>

    <!-- Alert de feedback -->
    <div id="auth-alert" class="auth-alert"></div>

    <!-- ===== FORM LOGIN ===== -->
    <form id="form-login" onsubmit="submitLogin(event)">
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
        <button type="submit" class="btn-auth" id="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
        </button>
        <p class="text-center mt-4 mb-0" style="font-size:0.85rem; color:#666;">
            Não tem conta? <a href="#" onclick="switchTab('cadastro')" style="color:#6366f1;">Cadastre-se</a>
        </p>
    </form>

    <!-- ===== FORM CADASTRO ===== -->
    <form id="form-cadastro" style="display:none;" onsubmit="submitCadastro(event)">
        <div class="mb-3">
            <label class="form-label">NOME COMPLETO</label>
            <div class="input-icon">
                <input type="text" id="cad-nome" class="form-control" placeholder="Seu nome" required>
                <i class="bi bi-person"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">E-MAIL</label>
            <div class="input-icon">
                <input type="email" id="cad-email" class="form-control" placeholder="seu@email.com" required>
                <i class="bi bi-envelope"></i>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label">CPF</label>
                <input type="text" id="cad-cpf" class="form-control" placeholder="000.000.000-00" maxlength="14" required>
            </div>
            <div class="col-6">
                <label class="form-label">TELEFONE</label>
                <input type="text" id="cad-telefone" class="form-control" placeholder="(41) 99999-9999" maxlength="15" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">SENHA</label>
            <div class="input-icon">
                <input type="password" id="cad-senha" class="form-control" placeholder="Mín. 6 caracteres" required>
                <i class="bi bi-lock"></i>
            </div>
        </div>
        <button type="submit" class="btn-auth" id="btn-cadastro">
            <i class="bi bi-person-plus me-2"></i>Criar Conta
        </button>
        <p class="text-center mt-4 mb-0" style="font-size:0.85rem; color:#666;">
            Já tem conta? <a href="#" onclick="switchTab('login')" style="color:#6366f1;">Entrar</a>
        </p>
    </form>
</div>

<script>
// ---- TABS ----
function switchTab(tab) {
    const isLogin = tab === 'login';
    document.getElementById('form-login').style.display    = isLogin ? 'block' : 'none';
    document.getElementById('form-cadastro').style.display = isLogin ? 'none'  : 'block';
    document.getElementById('tab-login').classList.toggle('active', isLogin);
    document.getElementById('tab-cadastro').classList.toggle('active', !isLogin);
    hideAlert();
}

// ---- ALERT ----
function showAlert(msg, type) {
    const el = document.getElementById('auth-alert');
    el.textContent = msg;
    el.className = 'auth-alert ' + type;
    el.style.display = 'block';
}
function hideAlert() {
    const el = document.getElementById('auth-alert');
    el.style.display = 'none';
}

// ---- MÁSCARA CPF ----
document.getElementById('cad-cpf').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    this.value = v;
});

// ---- MÁSCARA TELEFONE ----
document.getElementById('cad-telefone').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '');
    v = v.replace(/^(\d{2})(\d)/,  '($1) $2');
    v = v.replace(/(\d{5})(\d{4})$/, '$1-$2');
    this.value = v;
});

// ---- SUBMIT LOGIN ----
async function submitLogin(e) {
    e.preventDefault();
    hideAlert();
    const btn = document.getElementById('btn-login');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Entrando...';

    const data = new FormData();
    data.append('email', document.getElementById('login-email').value);
    data.append('senha', document.getElementById('login-senha').value);

    console.log("🚀 Enviando dados de login para o servidor...");
    try {
        const res  = await fetch('api/auth/login.php', { method: 'POST', body: data });
        const json = await res.json();
        console.log("📥 Resposta do servidor:", json);

        if (json.status === 'success') {
            showAlert('✅ ' + json.message, 'success');
            setTimeout(() => window.location.href = json.redirect, 800);
        } else {
            showAlert('❌ ' + json.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Entrar';
        }
    } catch {
        showAlert('❌ Erro de conexão. Tente novamente.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Entrar';
    }
}

// ---- SUBMIT CADASTRO ----
async function submitCadastro(e) {
    e.preventDefault();
    hideAlert();
    const btn = document.getElementById('btn-cadastro');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Criando conta...';

    const data = new FormData();
    data.append('nome',     document.getElementById('cad-nome').value);
    data.append('email',    document.getElementById('cad-email').value);
    data.append('cpf',      document.getElementById('cad-cpf').value);
    data.append('telefone', document.getElementById('cad-telefone').value);
    data.append('senha',    document.getElementById('cad-senha').value);

    console.log("🚀 Enviando dados de cadastro para o servidor...");
    try {
        const res  = await fetch('api/auth/cadastro.php', { method: 'POST', body: data });
        const json = await res.json();
        console.log("📥 Resposta do servidor:", json);

        if (json.status === 'success') {
            showAlert('✅ ' + json.message, 'success');
            setTimeout(() => window.location.href = json.redirect, 800);
        } else {
            showAlert('❌ ' + json.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus me-2"></i>Criar Conta';
        }
    } catch {
        showAlert('❌ Erro de conexão. Tente novamente.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus me-2"></i>Criar Conta';
    }
}
</script>

</body>
</html>
