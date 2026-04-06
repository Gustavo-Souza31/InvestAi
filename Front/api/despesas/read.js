async function listarDespesas(usuario_id) {
    const resposta = await fetch(`../backend/api/despesas/read.php`);
    return await resposta.json();
}
