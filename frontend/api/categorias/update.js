async function editarCategoriaNome(id, nomeAtual) {
    document.getElementById('edit-categoria-id').value = id;
    document.getElementById('edit-categoria-nome').value = nomeAtual;
    openModal('modal-edit-categoria');
}

async function salvarEdicaoCategoria() {
    const id = document.getElementById('edit-categoria-id').value;
    const novoNome = document.getElementById('edit-categoria-nome').value.trim();

    if (!novoNome) {
        showAlert('Digite o novo nome para a categoria.', 'error');
        return;
    }

    try {
        const response = await fetch('/inventai/backend/api/categorias/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nome: novoNome })
        });

        const data = await response.json();
        if (data.status === 'success') {
            closeModal('modal-edit-categoria');
            showAlert(data.message, 'success');

            const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';
            const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
            await carregarCategorias(tipo, selectIds);

            if (tipo === 'ganho' && typeof carregarGanhos === 'function') carregarGanhos();
            if (tipo === 'despesa' && typeof carregarDespesas === 'function') carregarDespesas();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Erro ao editar categoria:', error);
        showAlert('Erro de conexão.', 'error');
    }
}
