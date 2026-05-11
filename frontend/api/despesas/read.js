async function listarDespesas(usuario_id) {
    const resposta = await fetch(`${BASE_PATH}/backend/api/despesas/read.php`);
    return await resposta.json();
}
