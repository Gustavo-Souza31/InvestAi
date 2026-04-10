async function criarGanho() {

    // Coleta valores do formulário
    const descricao = document.getElementById('ganho-descricao').value.trim();
    const valor = document.getElementById('ganho-valor').value;
    const data = document.getElementById('ganho-data').value;
    const fixo = document.getElementById('ganho-fixo').checked;

    // Valida campos vazios
    if (!descricao || !valor || !data) {
        showAlert('Preencha todos os campos antes de enviar.', 'error');
        return;
    }

    // Prepara dados para envio
    const formData = new FormData();
    formData.append('descricao', descricao);
    formData.append('valor', parseFloat(valor));
    formData.append('data_ganho', data);
    formData.append('fixo', fixo ? 1 : 0);

    try {
        // Envia para backend
        const resposta = await fetch('../backend/api/ganhos/create.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        // Se erro na criação, mostra mensagem
        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao criar ganho.', 'error');
            return;
        }

        // Sucesso: mostra alerta, limpa campos e recarrega lista
        showAlert(resultado.message || 'Ganho criado com sucesso!', 'success');
        document.getElementById('ganho-valor').value = '';
        document.getElementById('ganho-data').value = new Date().toISOString().split('T')[0];
        document.getElementById('ganho-fixo').checked = false;

        // Recarrega ganhos na página
        if (typeof carregarGanhos === 'function') {
            carregarGanhos();
        }
    } catch (error) {
        // Erro de conexão
        console.error('Erro ao criar ganho:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// Listener do formulário
document.getElementById('form-ganho')?.addEventListener('submit', function (e) {
    e.preventDefault();
    criarGanho();
});
