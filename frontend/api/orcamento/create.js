async function criarOrcamento() {
    const categoriaValue = document.getElementById('orc-categoria').value;
    const limiteStr = document.getElementById('orc-limite').value.trim();
    const limite    = parseFloat(limiteStr);

    // Validações — usa showOrcAlert() para exibir erro DENTRO do modal
    if (categoriaValue === null || categoriaValue === undefined || categoriaValue === '') {
        showOrcAlert('Selecione uma categoria de despesa.', 'error');
        return;
    }
    if (limiteStr === '' || isNaN(limite)) {
        showOrcAlert('Informe um valor numérico válido.', 'error');
        return;
    }
    if (limite <= 0) {
        showOrcAlert('O limite deve ser maior que zero.', 'error');
        return;
    }

    try {
        const payload = { categoria_id: parseInt(categoriaValue), limite };

        const resposta = await fetch(BASE_PATH + '/backend/api/orcamento/create.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const resultado = await resposta.json();

        if (resultado.status === 'success') {
            fecharModalOrcamento();
            showAlert('Limite definido com sucesso! 🎯', 'success');
            carregarOrcamentos();
        } else {
            showOrcAlert(resultado.message || 'Erro ao salvar.', 'error');
        }
    } catch (error) {
        console.error('Erro ao criar orçamento:', error);
        showOrcAlert('Erro de conexão. Tente novamente.', 'error');
    }
}
