async function atualizarGanho() {

    // Coleta valores do modal de edição
    const id = document.getElementById('edit-id').value;
    const descricao = document.getElementById('edit-descricao').value.trim();
    const valor = document.getElementById('edit-valor').value;
    const data = document.getElementById('edit-data').value;
    const fixo = document.getElementById('edit-fixo').checked;

    // Valida campos vazios
    if (!descricao || !valor || !data) {
        showAlert('Preencha todos os campos.', 'error');
        return;
    }

    // Prepara dados para envio
    const formData = new FormData();
    formData.append('id', id);
    formData.append('descricao', descricao);
    formData.append('valor', parseFloat(valor));
    formData.append('data_ganho', data);
    formData.append('fixo', fixo ? 1 : 0);

    try {
        // Envia para backend
        const resposta = await fetch('../backend/api/ganhos/update.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        // Se erro na atualização, mostra mensagem
        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao atualizar ganho.', 'error');
            return;
        }

        // Sucesso: mostra alerta, fecha modal e recarrega
        showAlert(resultado.message || 'Ganho atualizado!', 'success');
        closeModal('modal-edit');

        // Recarrega ganhos na página
        if (typeof carregarGanhos === 'function') {
            carregarGanhos();
        }
    } catch (error) {
        // Erro de conexão
        console.error('Erro ao atualizar ganho:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// Listener do formulário de edição
document.getElementById('form-edit')?.addEventListener('submit', function(e) {
    e.preventDefault();
    atualizarGanho();
});
