async function excluirDespesa() {

    // Coleta ID do modal de confirmação
    const id = document.getElementById('delete-id').value;

    // Valida se ID foi informado
    if (!id) {
        showAlert('ID não informado.', 'error');
        return;
    }

    // Prepara dados para envio
    const formData = new FormData();
    formData.append('id', id);

    try {
        // Envia para backend
        const resposta = await fetch('../backend/api/despesas/delete.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        // Se erro na exclusão, mostra mensagem
        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao excluir despesa.', 'error');
            return;
        }

        // Sucesso: mostra alerta, fecha modal e recarrega
        showAlert(resultado.message || 'Despesa excluída!', 'success');
        closeModal('modal-delete');

        // Recarrega despesas na página
        if (typeof carregarDespesas === 'function') {
            carregarDespesas();
        }
    } catch (error) {
        // Erro de conexão
        console.error('Erro ao excluir despesa:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// Listener do botão de confirmação de exclusão
document.getElementById('btn-confirm-delete')?.addEventListener('click', excluirDespesa);
