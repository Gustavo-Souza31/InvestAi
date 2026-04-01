document.addEventListener('DOMContentLoaded', () => {
    const tabLogin = document.getElementById('tab-login');
    const tabCadastro = document.getElementById('tab-cadastro');
    const formLogin = document.getElementById('form-login');
    const formCadastro = document.getElementById('form-cadastro');

    // UI Tabs
    if (tabLogin && tabCadastro) {
        tabLogin.addEventListener('click', () => switchTab('login'));
        tabCadastro.addEventListener('click', () => switchTab('cadastro'));
    }

    function switchTab(tab) {
        const isLogin = tab === 'login';
        formLogin.style.display = isLogin ? 'block' : 'none';
        formCadastro.style.display = isLogin ? 'none' : 'block';
        tabLogin.classList.toggle('active', isLogin);
        tabCadastro.classList.toggle('active', !isLogin);
        hideAlert();
    }

    // Handlers
    if (formLogin) {
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();
            
            const btn = document.getElementById('btn-login');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Entrando...';

            const email = document.getElementById('login-email').value;
            const senha = document.getElementById('login-senha').value;

            try {
                const json = await efetuarLogin(email, senha);
                
                if (json.status === 'success') {
                    showAlert('✅ ' + json.message, 'success');
                    setTimeout(() => window.location.href = json.redirect, 800);
                } else {
                    showAlert('❌ ' + json.message, 'error');
                    resetBtn(btn, '<i class="bi bi-box-arrow-in-right me-2"></i>Entrar');
                }
            } catch {
                showAlert('❌ Erro de conexão. Tente novamente.', 'error');
                resetBtn(btn, '<i class="bi bi-box-arrow-in-right me-2"></i>Entrar');
            }
        });
    }

    if (formCadastro) {
        formCadastro.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();
            
            const btn = document.getElementById('btn-cadastro');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Criando conta...';

            const nome = document.getElementById('cadastro-nome').value;
            const email = document.getElementById('cadastro-email').value;
            const cpf = document.getElementById('cadastro-cpf').value;
            const telefone = document.getElementById('cadastro-telefone').value;
            const senha = document.getElementById('cadastro-senha').value;

            try {
                const json = await efetuarCadastro(nome, email, cpf, telefone, senha);
                
                if (json.status === 'success') {
                    showAlert('✅ ' + json.message, 'success');
                    setTimeout(() => window.location.href = json.redirect, 800);
                } else {
                    showAlert('❌ ' + json.message, 'error');
                    resetBtn(btn, '<i class="bi bi-person-plus me-2"></i>Criar Conta');
                }
            } catch {
                showAlert('❌ Erro de conexão. Tente novamente.', 'error');
                resetBtn(btn, '<i class="bi bi-person-plus me-2"></i>Criar Conta');
            }
        });
    }

    // Aux
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

    function resetBtn(btn, html) {
        btn.disabled = false;
        btn.innerHTML = html;
    }

    // Funcionalidade do link "Cadastre-se" do formulário de login
    const linkToCadastro = document.getElementById('link-to-cadastro');
    if(linkToCadastro) {
        linkToCadastro.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab('cadastro');
        });
    }

    // Funcionalidade do link "Entrar" do formulário de cadastro
    const linkToLogin = document.getElementById('link-to-login');
    if(linkToLogin) {
        linkToLogin.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab('login');
        });
    }
});
