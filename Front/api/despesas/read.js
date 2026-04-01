async function listarDespesas(usuario_id) {
    const res = await fetch(`../backend/api/despesas/read.php?usuario_id=${usuario_id}`);
    return await res.json();
}

async function criarDespesa(descricao, valor, data_despesa, fixo, usuario_id) {
    const res = await fetch('../backend/api/despesas/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ descricao, valor, data_despesa, fixo: fixo ? 1 : 0, usuario_id })
    });
    return await res.json();
}

async function editarDespesa(id, descricao, valor, data_despesa, fixo) {
    const res = await fetch('../backend/api/despesas/update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, descricao, valor, data_despesa, fixo: fixo ? 1 : 0 })
    });
    return await res.json();
}

async function deletarDespesa(id) {
    const res = await fetch('../backend/api/despesas/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    return await res.json();
}
