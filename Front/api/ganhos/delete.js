async function excluirGanho() {
    const id = document.getElementById('delete-id').value;

    if (!id) {
        showAlert('ID não informado.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);


    try {
        const resposta = await fetch('../backend/api/ganhos/delete.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao excluir ganho.', 'error');
            return;
        }

        showAlert(resultado.message || 'Ganho excluído!', 'success');
        closeModal('modal-delete');

        if (typeof carregarGanhos === 'function') {
            carregarGanhos();
        }
    } catch (error) {
        console.error('Erro ao excluir ganho:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}


document.getElementById('btn-confirm-delete')?.addEventListener('click', excluirGanho);
