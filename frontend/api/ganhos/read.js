async function listarGanhos(usuario_id) {
    const resposta = await fetch(`${BASE_PATH}/backend/api/ganhos/read.php`);
    return await resposta.json();
}
