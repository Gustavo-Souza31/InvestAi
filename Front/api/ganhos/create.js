async function criarGanho(descricao, valor, dataGanho, fixo, usuarioId) {
    try {
        const res = await fetch('../backend/api/ganhos/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                descricao: descricao,
                valor: parseFloat(valor),
                data_ganho: dataGanho,
                fixo: fixo,
                usuario_id: usuarioId
            })
        });
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao criar ganho:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
