async function criarCadastro() {
    
    // Coleta valores do formulário
    const nome = document.getElementById('cadastro-nome').value.trim();
    const email = document.getElementById('cadastro-email').value.trim();
    const cpf = document.getElementById('cadastro-cpf').value.replace(/\D/g, '');
    const telefone = document.getElementById('cadastro-telefone').value.replace(/\D/g, '');
    const senha = document.getElementById('cadastro-senha').value;

    // Valida campos vazios
    if (!nome || !email || !cpf || !telefone || !senha) {
        showAlert('Preencha todos os campos antes de enviar.', 'error');
        return;
    }

    // Prepara dados para envio
    const formData = new FormData();
    formData.append('nome', nome);
    formData.append('email', email);
    formData.append('cpf', cpf);
    formData.append('telefone', telefone);
    formData.append('senha', senha);

    try {
        // Envia para backend
        const resposta = await fetch('../backend/api/auth/cadastro.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        // Se erro no cadastro, mostra mensagem
        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao criar conta.', 'error');
            return;
        }

        // Sucesso: mostra alerta, limpa formulário e redireciona
        showAlert(resultado.message || 'Conta criada com sucesso!', 'success');

        // Limpa formulário
        document.getElementById('form-cadastro').reset();

        // Redireciona para login após 1.5s
        setTimeout(() => window.location.href = resultado.redirect, 1500);


    } catch (error) {
        // Erro de conexão
        console.error('Erro ao cadastrar:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// Listener do formulário
document.getElementById('form-cadastro')?.addEventListener('submit', function(e) {
    e.preventDefault();
    criarCadastro();
});
