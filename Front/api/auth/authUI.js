// ===== ABAS DE AUTENTICAÇÃO =====

function inicializarAbas() {
    const abaLogin = document.getElementById('tab-login');
    const abaCadastro = document.getElementById('tab-cadastro');
    const formularioLogin = document.getElementById('form-login');
    const formularioCadastro = document.getElementById('form-cadastro');
    const formularioRecuperar = document.getElementById('form-recuperar');

    if (!abaLogin || !abaCadastro) return;

    function trocarAba(aba) {
        // Alterna visibilidade dos formulários
        formularioLogin.style.display = aba === 'login' ? 'block' : 'none';
        formularioCadastro.style.display = aba === 'cadastro' ? 'block' : 'none';
        if (formularioRecuperar) {
            formularioRecuperar.style.display = aba === 'recuperar' ? 'block' : 'none';
        }

        // Alterna estado ativo das abas
        abaLogin.classList.toggle('active', aba === 'login');
        abaCadastro.classList.toggle('active', aba === 'cadastro');

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

    document.getElementById('link-recuperar')?.addEventListener('click', (evento) => {
        evento.preventDefault();
        trocarAba('recuperar');
    });

    document.getElementById('link-voltar-login')?.addEventListener('click', (evento) => {
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
