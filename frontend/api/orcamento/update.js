function abrirModalOrcamentoEdicao(categoriaId, categoriaNome, limite) {
    _orcModo = 'edit';
    document.getElementById('orc-categoria').value = categoriaId;
    document.getElementById('orc-limite').value    = limite;
    hideOrcAlert(); // Limpa erros de tentativas anteriores
    carregarCategoriasNoModal();
    document.getElementById('orcamento-overlay').classList.add('active');
    document.getElementById('orc-limite').focus();
}

async function atualizarOrcamento() {
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

        const resposta = await fetch(BASE_PATH + '/backend/api/orcamento/update.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const resultado = await resposta.json();

        if (resultado.status === 'success') {
            fecharModalOrcamento();
            showAlert('Limite atualizado com sucesso! 🎯', 'success');
            carregarOrcamentos();
        } else {
            showOrcAlert(resultado.message || 'Erro ao atualizar.', 'error');
        }
    } catch (error) {
        console.error('Erro ao atualizar orçamento:', error);
        showOrcAlert('Erro de conexão. Tente novamente.', 'error');
    }
}
