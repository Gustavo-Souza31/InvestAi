/**
 * tabs.js — Componente reutilizável de navegação entre abas
 *
 * Uso: inclua o script numa página que tenha os elementos:
 *   - #tab-login, #tab-cadastro  (botões das abas)
 *   - #form-login, #form-cadastro (painéis)
 *   - #link-to-cadastro, #link-to-login (links inline opcionais)
 *   - .auth-alert (elemento de feedback, opcional)
 *
 * O componente se inicializa automaticamente no DOMContentLoaded.
 */

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

document.addEventListener('DOMContentLoaded', initTabs);
