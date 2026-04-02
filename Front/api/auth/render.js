/**
 * auth/render.js — Inicialização e renderização da página de autenticação
 *
 * Responsável por:
 * - Alternar entre abas de login e cadastro
 * - Inicializar listeners de formulário
 * - Gerenciar estado visual da página
 */

// Inicializa o sistema de abas
function initTabs() {
    const tabLogin     = document.getElementById('tab-login');
    const tabCadastro  = document.getElementById('tab-cadastro');
    const formLogin    = document.getElementById('form-login');
    const formCadastro = document.getElementById('form-cadastro');

    if (!tabLogin || !tabCadastro) return;

    function switchTab(tab) {
        const isLogin = tab === 'login';
        formLogin.style.display    = isLogin ? 'block' : 'none';
        formCadastro.style.display = isLogin ? 'none'  : 'block';
        tabLogin.classList.toggle('active', isLogin);
        tabCadastro.classList.toggle('active', !isLogin);

        // Limpa o alerta ao trocar de aba
        const alert = document.querySelector('.auth-alert');
        if (alert) {
            alert.className   = 'auth-alert';
            alert.textContent = '';
        }
    }

    tabLogin.addEventListener('click', () => switchTab('login'));
    tabCadastro.addEventListener('click', () => switchTab('cadastro'));

    document.getElementById('link-to-cadastro')?.addEventListener('click', (e) => {
        e.preventDefault();
        switchTab('cadastro');
    });

    document.getElementById('link-to-login')?.addEventListener('click', (e) => {
        e.preventDefault();
        switchTab('login');
    });
}

// Inicializa listeners dos formulários
function initFormListeners() {
    // Listener do form de login
    document.getElementById('form-login')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await enviarLogin();
    });

    // Listener do form de cadastro
    document.getElementById('form-cadastro')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await enviarCadastro();
    });
}

// Inicializa a página
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initFormListeners();
});
