function abrirModalDeleteOrcamento(categoriaId, categoriaNome) {
    document.getElementById('orc-delete-id').value   = categoriaId;
    document.getElementById('orc-delete-nome').value = categoriaNome;
    document.getElementById('orc-modal-delete').classList.add('active');
}

function fecharModalDeleteOrcamento() {
    document.getElementById('orc-modal-delete').classList.remove('active');
}

async function confirmarDeleteOrcamento() {
    const categoriaId = document.getElementById('orc-delete-id').value;

    try {
        const resposta = await fetch(BASE_PATH + '/backend/api/orcamento/delete.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ categoria_id: parseInt(categoriaId) }),
        });
        const resultado = await resposta.json();

        if (resultado.status === 'success') {
            fecharModalDeleteOrcamento();
            showAlert('Orçamento deletado com sucesso! 🗑️', 'success');
            carregarOrcamentos();
        } else {
            showAlert(resultado.message || 'Erro ao deletar orçamento.', 'error');
        }
    } catch (e) {
        console.error('Erro ao deletar:', e);
        showAlert('Erro de conexão ao deletar.', 'error');
    }
}
