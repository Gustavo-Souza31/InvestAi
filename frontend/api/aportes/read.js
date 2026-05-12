async function listarAportes(metaId) {
    const resposta = await fetch(BASE_PATH + '/backend/api/aportes/read.php?meta_id=' + encodeURIComponent(metaId));
    return await resposta.json();
}
