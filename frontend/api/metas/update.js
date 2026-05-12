async function atualizarMeta() {
    const metaId = document.getElementById('meta-id').value;
    const nomeStr = document.getElementById('meta-nome').value.trim();
    const valorStr = document.getElementById('meta-valor').value.trim();
    const prazo = document.getElementById('meta-prazo').value.trim();
    const valor = parseFloat(valorStr);

    if (!metaId) {
        showAlert('ID da meta não encontrado.', 'error');
        return;
    }
    if (!nomeStr) {
        showAlert('Preencha o nome da meta.', 'error');
        return;
    }
    if (nomeStr.length < 3) {
        showAlert('O nome deve ter pelo menos 3 caracteres.', 'error');
        return;
    }
    if (valorStr === '') {
        showAlert('Preencha o valor total da meta.', 'error');
        return;
    }
    if (isNaN(valor)) {
        showAlert('Informe um valor numérico válido.', 'error');
        return;
    }
    if (valor <= 0) {
        showAlert('O valor deve ser maior que zero.', 'error');
        return;
    }
    if (valor > 99999999.99) {
        showAlert('O valor é muito grande (máximo R$ 99.999.999,99).', 'error');
        return;
    }
    if (prazo) {
        const dataRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!dataRegex.test(prazo) || isNaN(new Date(prazo).getTime())) {
            showAlert('Data de prazo inválida.', 'error');
            return;
        }
    }

    try {
        const formData = new FormData();
        formData.append('id', metaId);
        formData.append('nome', nomeStr);
        formData.append('valor_total', valor);
        if (prazo) formData.append('prazo', prazo);

        const resposta = await fetch(BASE_PATH + '/backend/api/metas/update.php', {
            method: 'POST',
            body: formData,
        });
        const resultado = await resposta.json();

        if (resultado.status === 'success') {
            fecharModalMeta();
            showAlert(resultado.message || 'Meta atualizada com sucesso! 🎯', 'success');
            carregarMetas();
        } else {
            showAlert(resultado.message || 'Erro ao atualizar meta.', 'error');
        }
    } catch (error) {
        console.error('Erro ao atualizar meta:', error);
        showAlert('Erro de conexão. Tente novamente.', 'error');
    }
}
