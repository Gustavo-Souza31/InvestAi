async function atualizarDespesa() {
    const id        = document.getElementById('edit-id').value;
    const descricao = document.getElementById('edit-descricao').value.trim();
    const valor     = document.getElementById('edit-valor').value;
    const data      = document.getElementById('edit-data').value;
    const fixo      = document.getElementById('edit-fixo').checked;

    if (!descricao || !valor || !data) {
        showAlert('Preencha todos os campos.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('id',           id);
    formData.append('descricao',    descricao);
    formData.append('valor',        parseFloat(valor));
    formData.append('data_despesa', data);
    formData.append('fixo',         fixo ? 1 : 0);


    try {
        const resposta = await fetch('../backend/api/despesas/update.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao atualizar despesa.', 'error');
            return;
        }

        showAlert(resultado.message || 'Despesa atualizada!', 'success');
        closeModal('modal-edit');

        if (typeof carregarDespesas === 'function') {
            carregarDespesas();
        }
    } catch (error) {
        console.error('Erro ao atualizar despesa:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}


document.getElementById('form-edit')?.addEventListener('submit', function(e) {
    e.preventDefault();
    atualizarDespesa();
});
