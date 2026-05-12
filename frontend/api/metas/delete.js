function abrirExclusaoMeta(id, nome) {
    document.getElementById('meta-delete-id').value = id;
    document.getElementById('meta-delete-nome').value = nome;
    document.getElementById('meta-modal-delete').classList.add('active');
}

function fecharExclusaoMeta() {
    document.getElementById('meta-modal-delete').classList.remove('active');
}

async function excluirMeta() {
    const id = document.getElementById('meta-delete-id').value;

    if (!id) {
        showAlert('ID da meta não encontrado.', 'error');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);

        const resposta = await fetch(BASE_PATH + '/backend/api/metas/delete.php', {
            method: 'POST',
            body: formData,
        });
        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao remover meta.', 'error');
            return;
        }
        showAlert(resultado.message || 'Meta removida com sucesso!', 'success');
        fecharExclusaoMeta();
        if (typeof carregarMetas === 'function') carregarMetas();
    } catch (error) {
        console.error('Erro ao excluir meta:', error);
        showAlert('Erro de conexão.', 'error');
    }
}

document.getElementById('meta-btn-confirm-delete')?.addEventListener('click', excluirMeta);
