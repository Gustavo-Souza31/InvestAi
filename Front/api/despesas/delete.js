async function excluirDespesa() {
    const id = document.getElementById('delete-id').value;

    if (!id) {
        showAlert('ID não informado.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);


    try {
        const resposta = await fetch('/inventai/backend/api/despesas/delete.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao excluir despesa.', 'error');
            return;
        }

        showAlert(resultado.message || 'Despesa excluída!', 'success');
        closeModal('modal-delete');

        if (typeof carregarDespesas === 'function') {
            carregarDespesas();
        }
    } catch (error) {
        console.error('Erro ao excluir despesa:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}


document.getElementById('btn-confirm-delete')?.addEventListener('click', excluirDespesa);
