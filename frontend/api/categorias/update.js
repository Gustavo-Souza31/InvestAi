async function editarCategoriaNome(id, nomeAtual) {
    document.getElementById('edit-categoria-id').value = id;
    document.getElementById('edit-categoria-nome').value = nomeAtual;
    openModal('modal-edit-categoria');
}

async function atualizarCategoria() {
    const id = document.getElementById('edit-categoria-id').value;
    const novoNome = document.getElementById('edit-categoria-nome').value.trim();

    if (!novoNome) {
        showAlert('Digite o novo nome para a categoria.', 'error');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('nome', novoNome);

        const resposta = await fetch(BASE_PATH + '/backend/api/categorias/update.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();
        if (resultado.status === 'success') {
            closeModal('modal-edit-categoria');
            showAlert(resultado.message || 'Categoria atualizada com sucesso.', 'success');

            const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';
            const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
            await carregarCategorias(tipo, selectIds);

            if (tipo === 'ganho' && typeof carregarGanhos === 'function') carregarGanhos();
            if (tipo === 'despesa' && typeof carregarDespesas === 'function') carregarDespesas();
        } else {
            showAlert(resultado.message || 'Erro ao atualizar categoria.', 'error');
        }
    } catch (error) {
        console.error('Erro ao atualizar categoria:', error);
        showAlert('Erro de conexão.', 'error');
    }
}
