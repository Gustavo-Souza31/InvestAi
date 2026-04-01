// UI Tabs
const tabLogin = document.getElementById('tab-login');
const tabCadastro = document.getElementById('tab-cadastro');
const formLogin = document.getElementById('form-login');
const formCadastro = document.getElementById('form-cadastro');
const authAlert = document.getElementById('auth-alert');

function switchTab(tab) {
    const isLogin = tab === 'login';
    formLogin.style.display = isLogin ? 'block' : 'none';
    formCadastro.style.display = isLogin ? 'none' : 'block';
    tabLogin.classList.toggle('active', isLogin);
    tabCadastro.classList.toggle('active', !isLogin);
    hideAlert();
}

function showAlert(message, type) {
    authAlert.className = `auth-alert ${type} show`;
    authAlert.textContent = message;
}

function hideAlert() {
    authAlert.className = 'auth-alert';
    authAlert.textContent = '';
}

// Máscaras de entrada
const cpfInput = document.getElementById('cadastro-cpf');
if (cpfInput) {
    cpfInput.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        this.value = v;
    });
}

const telInput = document.getElementById('cadastro-telefone');
if (telInput) {
    telInput.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        v = v.replace(/^(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{5})(\d{4})$/, '$1-$2');
        this.value = v;
    });
}

// Evento nos tabs
tabLogin.addEventListener('click', () => switchTab('login'));
tabCadastro.addEventListener('click', () => switchTab('cadastro'));

// Link para tabs
document.getElementById('link-to-cadastro').addEventListener('click', (e) => {
    e.preventDefault();
    switchTab('cadastro');
});

document.getElementById('link-to-login').addEventListener('click', (e) => {
    e.preventDefault();
    switchTab('login');
});

// Login submit
formLogin.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();

    const btn = document.getElementById('btn-login');
    const email = document.getElementById('login-email').value;
    const senha = document.getElementById('login-senha').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Entrando...';

    const json = await efetuarLogin(email, senha);

    if (json.status === 'success') {
        showAlert('✅ ' + json.message, 'success');
        setTimeout(() => window.location.href = json.redirect, 800);
    } else {
        showAlert('❌ ' + json.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Entrar';
    }
});

// Cadastro submit
formCadastro.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();

    const btn = document.getElementById('btn-cadastro');
    const nome = document.getElementById('cadastro-nome').value;
    const email = document.getElementById('cadastro-email').value;
    const cpf = document.getElementById('cadastro-cpf').value;
    const telefone = document.getElementById('cadastro-telefone').value;
    const senha = document.getElementById('cadastro-senha').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Criando...';

    const json = await efetuarCadastro(nome, email, cpf, telefone, senha);

    if (json.status === 'success') {
        showAlert('✅ ' + json.message, 'success');
        setTimeout(() => window.location.href = json.redirect, 800);
    } else {
        showAlert('❌ ' + json.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus me-2"></i>Criar Conta';
    }
});

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
