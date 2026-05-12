function abrirExclusaoOrcamento(categoriaId, categoriaNome) {
    document.getElementById('orc-delete-id').value   = categoriaId;
    document.getElementById('orc-delete-nome').value = categoriaNome;
    document.getElementById('orc-modal-delete').classList.add('active');
}

function fecharExclusaoOrcamento() {
    document.getElementById('orc-modal-delete').classList.remove('active');
}

async function excluirOrcamento() {
    const categoriaId = document.getElementById('orc-delete-id').value;

    try {
        const resposta = await fetch(BASE_PATH + '/backend/api/orcamento/delete.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ categoria_id: parseInt(categoriaId) }),
        });
        const resultado = await resposta.json();

        if (resultado.status === 'success') {
            fecharExclusaoOrcamento();
            showAlert(resultado.message || 'Orçamento excluído com sucesso!', 'success');
            carregarOrcamentos();
        } else {
            showAlert(resultado.message || 'Erro ao excluir orçamento.', 'error');
        }
    } catch (error) {
        console.error('Erro ao excluir orçamento:', error);
        showAlert('Erro de conexão ao excluir.', 'error');
    }
}
