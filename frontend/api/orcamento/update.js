function abrirModalOrcamentoEdicao(categoriaId, categoriaNome, limite) {
    _orcModo = 'edit';
    document.getElementById('orc-categoria').value = categoriaId;
    document.getElementById('orc-limite').value    = limite;
    ocultarAlertOrc();
    carregarCategoriasNoModal();
    document.getElementById('orcamento-overlay').classList.add('active');
    document.getElementById('orc-limite').focus();
}

async function atualizarOrcamento() {
    const categoriaValue = document.getElementById('orc-categoria').value;
    const limiteStr = document.getElementById('orc-limite').value.trim();
    const limite    = parseFloat(limiteStr);

    // Validações
    if (categoriaValue === null || categoriaValue === undefined || categoriaValue === '') {
        mostrarAlertOrc('Selecione uma categoria de despesa.', 'erro');
        return;
    }
    if (limiteStr === '' || isNaN(limite)) {
        mostrarAlertOrc('Informe um valor numérico válido.', 'erro');
        return;
    }
    if (limite <= 0) {
        mostrarAlertOrc('O limite deve ser maior que zero.', 'erro');
        return;
    }

    const btn = document.getElementById('orc-btn-salvar');
    btn.disabled = true;
    btn.innerHTML = '<div class="orc-spinner"></div> Salvando...';

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
            mostrarAlertOrc(resultado.message || 'Erro ao atualizar.', 'erro');
        }
    } catch (e) {
        mostrarAlertOrc('Erro de conexão. Tente novamente.', 'erro');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-all me-1"></i>Salvar Limite';
    }
}
