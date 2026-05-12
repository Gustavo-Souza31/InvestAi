function abrirExclusaoAporte(id) {
    document.getElementById('delete-aporte-id').value = id;
    openModal('modal-delete-aporte');
}

async function excluirAporte() {
    const id = document.getElementById('delete-aporte-id').value;

    const formData = new FormData();
    formData.append('id', id);

    try {
        const resposta = await fetch(BASE_PATH + '/backend/api/aportes/delete.php', { method: 'POST', body: formData });
        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao excluir aporte.', 'error');
            return;
        }
        closeModal('modal-delete-aporte');
        showAlert('Aporte excluído.', 'success');
        const metaId = document.getElementById('aporte-meta-id').value;
        carregarAportes(parseInt(metaId));
        if (typeof carregarMetas === 'function') carregarMetas();
        if (typeof carregarDashboard === 'function') carregarDashboard();
    } catch (error) {
        console.error('Erro ao excluir aporte:', error);
        showAlert('Erro de conexão.', 'error');
    }
}
