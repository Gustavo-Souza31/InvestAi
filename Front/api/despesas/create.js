async function criarDespesa() {
    const descricao = document.getElementById('despesa-descricao').value.trim();
    const valor     = document.getElementById('despesa-valor').value;
    const data      = document.getElementById('despesa-data').value;
    const fixo      = document.getElementById('despesa-fixo').checked;

    if (!descricao || !valor || !data) {
        showAlert('Preencha todos os campos antes de enviar.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('descricao',    descricao);
    formData.append('valor',        parseFloat(valor));
    formData.append('data_despesa', data);
    formData.append('fixo',         fixo ? 1 : 0);

    try {
        const resposta = await fetch('/inventai/backend/api/despesas/create.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao criar despesa.', 'error');
            return;
        }

        showAlert(resultado.message || 'Despesa criada com sucesso.', 'success');

        document.getElementById('despesa-descricao').value = '';
        document.getElementById('despesa-valor').value     = '';
        document.getElementById('despesa-data').value      = new Date().toISOString().split('T')[0];
        document.getElementById('despesa-fixo').checked    = false;

        if (typeof carregarDespesas === 'function') {
            carregarDespesas();
        }
    } catch (error) {
        console.error('Erro ao criar despesa:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

document.getElementById('form-despesa')?.addEventListener('submit', function(e) {
    e.preventDefault();
    criarDespesa();
});
