async function efetuarCadastro(nome, email, cpf, telefone, senha) {
    try {
        const res = await fetch('../backend/api/auth/cadastro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nome: nome,
                email: email,
                cpf: cpf,
                telefone: telefone,
                senha: senha
            })
        });
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao efetuar cadastro:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
