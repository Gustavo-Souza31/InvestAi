async function efetuarLogin(email, senha) {
    try {
        const res = await fetch('../backend/api/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, senha: senha })
        });
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao efetuar login:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
