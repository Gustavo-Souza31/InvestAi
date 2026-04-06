/**
 * auth/cadastro.js — Lógica de cadastro de usuário
 *
 * Funções:
 * - enviarCadastro(): Coleta dados, valida e envia para o backend
 *
 * O listener do formulário está em auth/render.js
 */

// Função auxiliar para enviar dados de cadastro
async function efetuarCadastro(nome, email, cpf, telefone, senha) {
    const formData = new FormData();
    formData.append('nome', nome);
    formData.append('email', email);
    formData.append('cpf', cpf);
    formData.append('telefone', telefone);
    formData.append('senha', senha);

    const resposta = await fetch('../backend/api/auth/cadastro.php', {
        method: 'POST',
        body: formData
    });
    return await resposta.json();
}

// Função principal de envio de cadastro
async function enviarCadastro() {
    const btn = document.getElementById('btn-cadastro');
    const nome = document.getElementById('cadastro-nome').value.trim();
    const email = document.getElementById('cadastro-email').value.trim();
    const cpf = document.getElementById('cadastro-cpf').value.replace(/\D/g, '');
    const telefone = document.getElementById('cadastro-telefone').value.replace(/\D/g, '');
    const senha = document.getElementById('cadastro-senha').value;

    // Validar campos nulos
    if (!nome || !email || !cpf || !telefone || !senha) {
        showAlert('Preencha todos os campos.', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Criando...';

    try {
        const resultado = await efetuarCadastro(nome, email, cpf, telefone, senha);

        if (resultado.status === 'success') {
            showAlert(resultado.message, 'success');
            document.getElementById('form-cadastro').reset();
            setTimeout(() => window.location.href = resultado.redirect, 1500);
        } else {
            showAlert(resultado.message || 'Erro ao criar conta.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus me-2"></i>Criar Conta';
        }
    } catch (error) {
        console.error('Erro ao cadastrar:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus me-2"></i>Criar Conta';
    }
}
