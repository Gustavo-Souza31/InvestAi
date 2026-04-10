// ===== ABAS DE AUTENTICAÇÃO =====

function inicializarAbas() {
    const abaLogin = document.getElementById('tab-login');
    const abaCadastro = document.getElementById('tab-cadastro');
    const formularioLogin = document.getElementById('form-login');
    const formularioCadastro = document.getElementById('form-cadastro');

    if (!abaLogin || !abaCadastro) return;

    function trocarAba(aba) {
        const ehLogin = aba === 'login';

        // Alterna visibilidade dos formulários
        formularioLogin.style.display = ehLogin ? 'block' : 'none';
        formularioCadastro.style.display = ehLogin ? 'none' : 'block';

        // Alterna estado ativo das abas
        abaLogin.classList.toggle('active', ehLogin);
        abaCadastro.classList.toggle('active', !ehLogin);

        // Limpa alerta anterior
        const alerta = document.querySelector('.auth-alert');
        if (alerta) {
            alerta.className = 'auth-alert';
            alerta.textContent = '';
        }
    }

    // Listeners para abas
    abaLogin.addEventListener('click', () => trocarAba('login'));
    abaCadastro.addEventListener('click', () => trocarAba('cadastro'));

    // Listeners para links de navegação
    document.getElementById('link-to-cadastro')?.addEventListener('click', (evento) => {
        evento.preventDefault();
        trocarAba('cadastro');
    });

    document.getElementById('link-to-login')?.addEventListener('click', (evento) => {
        evento.preventDefault();
        trocarAba('login');
    });
}

// ===== LISTENERS DE FORMULÁRIOS =====

function configurarListenersFormularios() {

    // Nota: Listeners de login e cadastro estão em login.js e cadastro.js para evitar duplicação
}

// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    inicializarAbas();
    configurarListenersFormularios();
});
