async function criarDespesa() {

    // Coleta valores do formulário
    const descricao = document.getElementById('despesa-descricao').value.trim();
    const valor = document.getElementById('despesa-valor').value;
    const data = document.getElementById('despesa-data').value;
    const fixo = document.getElementById('despesa-fixo').checked;

    // Valida campos vazios
    if (!descricao || !valor || !data) {
        showAlert('Preencha todos os campos antes de enviar.', 'error');
        return;
    }

    // Prepara dados para envio
    const formData = new FormData();
    formData.append('descricao', descricao);
    formData.append('valor', parseFloat(valor));
    formData.append('data_despesa', data);
    formData.append('fixo', fixo ? 1 : 0);

    try {
        // Envia para backend
        const resposta = await fetch('../backend/api/despesas/create.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        // Se erro na criação, mostra mensagem
        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao criar despesa.', 'error');
            return;
        }

        // Sucesso: mostra alerta, limpa campos e recarrega lista
        showAlert(resultado.message || 'Despesa criada com sucesso!', 'success');
        document.getElementById('despesa-valor').value = '';
        document.getElementById('despesa-data').value = new Date().toISOString().split('T')[0];
        document.getElementById('despesa-fixo').checked = false;

        // Recarrega despesas na página
        if (typeof carregarDespesas === 'function') {
            carregarDespesas();
        }
    } catch (error) {
        // Erro de conexão
        console.error('Erro ao criar despesa:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// Listener do formulário
document.getElementById('form-despesa')?.addEventListener('submit', function (e) {
    e.preventDefault();
    criarDespesa();
});
