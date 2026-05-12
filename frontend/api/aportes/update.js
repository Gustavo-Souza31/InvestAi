function abrirEdicaoAporte(id, valor, data) {
    document.getElementById('edit-aporte-id').value = id;
    document.getElementById('edit-aporte-valor').value = valor;
    document.getElementById('edit-aporte-data').value = data;
    openModal('modal-edit-aporte');
}

async function atualizarAporte() {
    const id    = document.getElementById('edit-aporte-id').value;
    const valor = parseFloat(document.getElementById('edit-aporte-valor').value);
    const data  = document.getElementById('edit-aporte-data').value;

    if (!valor || valor <= 0) {
        showAlert('Valor deve ser maior que zero.', 'error');
        return;
    }
    if (!data) {
        showAlert('Data é obrigatória.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('valor', valor);
    formData.append('data_aporte', data);

    try {
        const resposta = await fetch(BASE_PATH + '/backend/api/aportes/update.php', { method: 'POST', body: formData });
        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao atualizar aporte.', 'error');
            return;
        }
        closeModal('modal-edit-aporte');
        showAlert('Aporte atualizado.', 'success');
        const metaId = document.getElementById('aporte-meta-id').value;
        carregarAportes(parseInt(metaId));
        if (typeof carregarMetas === 'function') carregarMetas();
        if (typeof carregarDashboard === 'function') carregarDashboard();
    } catch (error) {
        console.error('Erro ao atualizar aporte:', error);
        showAlert('Erro de conexão.', 'error');
    }
}
