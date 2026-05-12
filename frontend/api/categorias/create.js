async function criarCategoria() {
    const input = document.getElementById('nova-categoria-nome');
    const nome = input.value.trim();
    const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';

    if (!nome) {
        showAlert('Digite o nome da categoria.', 'error');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('nome', nome);
        formData.append('tipo', tipo);

        const resposta = await fetch(BASE_PATH + '/backend/api/categorias/create.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();
        if (resultado.status === 'success') {
            input.value = '';
            showAlert(resultado.message || 'Categoria criada com sucesso!', 'success');
            const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
            await carregarCategorias(tipo, selectIds);
        } else {
            showAlert(resultado.message || 'Erro ao criar categoria.', 'error');
        }
    } catch (error) {
        console.error('Erro ao criar categoria:', error);
        showAlert('Erro de conexão.', 'error');
    }
}
