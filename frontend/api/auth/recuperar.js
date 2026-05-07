async function recuperarSenha() {
    // Coleta valores do formulário
    const email = document.getElementById('recuperar-email').value.trim();
    const novaSenha = document.getElementById('recuperar-nova-senha').value;

    if (!email || !novaSenha) {
        showAlert('Por favor, preencha todos os campos.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('email', email);
    formData.append('nova_senha', novaSenha);

    try {
        const response = await fetch('/inventai/backend/api/auth/recuperar.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await response.json();

        if (resultado.status === 'error') {
            showAlert(resultado.message || 'Erro ao alterar a senha.', 'error');
            return;
        }

        // Sucesso: mostra alerta e volta para login
        showAlert('Senha alterada com sucesso, você já pode fazer login', 'success');

        // Limpa o formulário
        document.getElementById('form-recuperar').reset();

        // Volta para a aba de login após exibir o sucesso (aguarda 2 segundos)
        setTimeout(() => {
            const abaLogin = document.getElementById('tab-login');
            if (abaLogin) abaLogin.click();
        }, 2000);

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
