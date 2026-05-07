async function adicionarCategoria() {
    const input = document.getElementById('nova-categoria-nome');
    const nome = input.value.trim();
    const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';

    if (!nome) {
        showAlert('Digite o nome da categoria.', 'error');
        return;
    }

    try {
        const response = await fetch('/inventai/backend/api/categorias/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome, tipo })
        });

        const data = await response.json();
        if (data.status === 'success') {
            input.value = '';
            showAlert(data.message, 'success');
            const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
            await carregarCategorias(tipo, selectIds);
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Erro ao adicionar categoria:', error);
        showAlert('Erro de conexão.', 'error');
    }
}
