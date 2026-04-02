/**
 * auth/login.js — Lógica de login de usuário
 *
 * Funções:
 * - enviarLogin(): Coleta dados, valida e envia para o backend
 *
 * O listener do formulário está em auth/render.js
 */

// Função auxiliar para enviar dados de login
async function efetuarLogin(email, senha) {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('senha', senha);

    const resposta = await fetch('/inventai/backend/api/auth/login.php', {
        method: 'POST',
        body: formData
    });
    return await resposta.json();
}

// Função principal de envio de login
async function enviarLogin() {
    const btn   = document.getElementById('btn-login');
    const email = document.getElementById('login-email').value.trim();
    const senha = document.getElementById('login-senha').value;

    // Validar campos nulos
    if (!email || !senha) {
        showAlert('Preencha e-mail e senha.', 'error');
        return;
    }

    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Entrando...';

    try {
        const resultado = await efetuarLogin(email, senha);

        if (resultado.status === 'success') {
            showAlert(resultado.message, 'success');
            setTimeout(() => window.location.href = resultado.redirect, 1500);
        } else {
            showAlert(resultado.message || 'Erro ao fazer login.', 'error');
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Entrar';
        }
    } catch (error) {
        console.error('Erro ao fazer login:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Entrar';
    }
}

