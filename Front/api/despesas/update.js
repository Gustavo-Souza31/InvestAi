async function atualizarDespesa(id, descricao, valor, dataDespesa, fixo) {
    try {
        const res = await fetch('../backend/api/despesas/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                descricao: descricao,
                valor: parseFloat(valor),
                data_despesa: dataDespesa,
                fixo: fixo
            })
        });
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao atualizar despesa:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
