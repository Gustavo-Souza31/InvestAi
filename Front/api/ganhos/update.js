async function atualizarGanho(id, descricao, valor, dataGanho, fixo) {
    try {
        const res = await fetch('/backend/api/ganhos/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                descricao: descricao,
                valor: parseFloat(valor),
                data_ganho: dataGanho,
                fixo: fixo
            })
        });
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao atualizar ganho:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
