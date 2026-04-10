async function fazerLogin() {

    // Coleta valores do formulário
    const email = document.getElementById('login-email').value.trim();
    const senha = document.getElementById('login-senha').value;

    // Valida campos vazios
    if (!email || !senha) {
        showAlert('Preencha todos os campos.', 'error');
        return;
    }

    // Prepara dados para envio
    const formData = new FormData();
    formData.append('email', email);
    formData.append('senha', senha);

    try {
        // Envia para backend
        const resposta = await fetch('../backend/api/auth/login.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        // Se erro no login, mostra mensagem
        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'E-mail ou senha inválidos.', 'error');
            return;
        }

        // Sucesso: mostra alerta e redireciona
        showAlert(resultado.message || 'Login realizado!', 'success');

        // Redireciona para dashboard após 1.5s
        setTimeout(() => window.location.href = resultado.redirect, 1500);

    } catch (error) {
        // Erro de conexão
        console.error('Erro ao fazer login:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// Listener do formulário
document.getElementById('form-login')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fazerLogin();
});

