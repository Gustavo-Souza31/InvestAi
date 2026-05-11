function excluirCategoria(id) {
    document.getElementById('delete-categoria-id').value = id;
    openModal('modal-delete-categoria');
}

async function confirmarExclusaoCategoria() {
    const id = document.getElementById('delete-categoria-id').value;
    try {
        const response = await fetch(BASE_PATH + '/backend/api/categorias/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        const data = await response.json();
        if (data.status === 'success') {
            closeModal('modal-delete-categoria');
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
        console.error('Erro ao excluir categoria:', error);
        showAlert('Erro de conexão.', 'error');
    }
}
