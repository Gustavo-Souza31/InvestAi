async function listarDespesas(usuario_id) {
    const resposta = await fetch(`/inventai/backend/api/despesas/read.php`);
    return await resposta.json();
}
