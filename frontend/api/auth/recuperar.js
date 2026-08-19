// ===== ESTADO: token presente na URL define se estamos na etapa 1 (pedir link) ou 2 (nova senha) =====

function _recuperarTemToken() {
    const el = document.getElementById('recuperar-token');
    return !!(el && el.value);
}

function _recuperarAtualizarEtapa() {
    const temToken = _recuperarTemToken();
    const stepEmail = document.getElementById('recuperar-step-email');
    const stepSenha = document.getElementById('recuperar-step-senha');
    const label = document.getElementById('btn-recuperar-label');

    if (!stepEmail || !stepSenha || !label) return;

    stepEmail.style.display = temToken ? 'none' : 'block';
    stepSenha.style.display = temToken ? 'block' : 'none';
    label.textContent = temToken ? 'Alterar Senha' : 'Enviar Link de Recuperação';
}

async function recuperarSenha() {
    const temToken = _recuperarTemToken();
    const formData = new FormData();

    if (temToken) {
        const novaSenha = document.getElementById('recuperar-nova-senha').value;
        if (!novaSenha) {
            showAlert('Informe a nova senha.', 'warning');
            return;
        }
        formData.append('token', document.getElementById('recuperar-token').value);
        formData.append('nova_senha', novaSenha);
    } else {
        const email = document.getElementById('recuperar-email').value.trim();
        if (!email) {
            showAlert('Informe seu e-mail.', 'warning');
            return;
        }
        formData.append('email', email);
    }

    try {
        const response = await fetch(BASE_PATH + '/backend/api/auth/recuperar.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await response.json();

        if (resultado.status === 'error') {
            showAlert(resultado.message || 'Erro ao processar solicitação.', 'error');
            return;
        }

        showAlert(resultado.message, 'success');
        document.getElementById('form-recuperar').reset();

        if (temToken) {
            // Senha alterada: volta para a aba de login após exibir o sucesso
            setTimeout(() => {
                const abaLogin = document.getElementById('tab-login');
                if (abaLogin) abaLogin.click();
            }, 2000);
        }

    } catch (error) {
        console.error('Erro ao recuperar senha:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// Listener do formulário
document.getElementById('form-recuperar')?.addEventListener('submit', function (e) {
    e.preventDefault();
    recuperarSenha();
});

document.addEventListener('DOMContentLoaded', () => {
    _recuperarAtualizarEtapa();

    // Se veio um link de redefinição por e-mail, abre direto na aba de recuperação
    if (_recuperarTemToken()) {
        document.getElementById('tab-login')?.classList.remove('active');
        document.getElementById('form-login').style.display = 'none';
        document.getElementById('form-cadastro').style.display = 'none';
        document.getElementById('form-recuperar').style.display = 'block';
    }
});
