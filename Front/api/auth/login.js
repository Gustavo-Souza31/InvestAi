async function efetuarLogin(email, senha) {
    const res = await fetch('../backend/api/auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, senha: senha })
    });
    return await res.json();
}

async function efetuarCadastro(nome, email, cpf, telefone, senha) {
    const res = await fetch('../backend/api/auth/cadastro.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome: nome, email: email, cpf: cpf, telefone: telefone, senha: senha })
    });
    return await res.json();
}
