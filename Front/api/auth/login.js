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

document.getElementById('form-login')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn   = document.getElementById('btn-login');
    const email = document.getElementById('login-email').value.trim();
    const senha = document.getElementById('login-senha').value;

    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Entrando...';

    try {
        const resultado = await efetuarLogin(email, senha);

        if (resultado.status === 'success') {
            showAlert(resultado.message, 'success');
            setTimeout(() => window.location.href = resultado.redirect, 800);
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
});
