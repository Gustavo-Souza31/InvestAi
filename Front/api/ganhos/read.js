async function listarGanhos(usuario_id) {
    const res = await fetch(`../backend/api/ganhos/read.php?usuario_id=${usuario_id}`);
    return await res.json();
}

async function criarGanho(descricao, valor, data_ganho, fixo, usuario_id) {
    const res = await fetch('../backend/api/ganhos/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ descricao, valor, data_ganho, fixo: fixo ? 1 : 0, usuario_id })
    });
    return await res.json();
}

async function editarGanho(id, descricao, valor, data_ganho, fixo) {
    const res = await fetch('../backend/api/ganhos/update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, descricao, valor, data_ganho, fixo: fixo ? 1 : 0 })
    });
    return await res.json();
}

async function deletarGanho(id) {
    const res = await fetch('../backend/api/ganhos/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    return await res.json();
}
