async function listarGanhos(usuario_id) {
    const resposta = await fetch(`/inventai/backend/api/ganhos/read.php`);
    return await resposta.json();
}
