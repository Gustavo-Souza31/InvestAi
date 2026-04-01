async function criarDespesa(descricao, valor, dataDespesa, fixo, usuarioId) {
    try {
        const res = await fetch('../backend/api/despesas/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                descricao: descricao,
                valor: parseFloat(valor),
                data_despesa: dataDespesa,
                fixo: fixo,
                usuario_id: usuarioId
            })
        });
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao criar despesa:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
