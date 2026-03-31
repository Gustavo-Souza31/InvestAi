async function listarGanhos(usuarioId) {
    try {
        const res = await fetch(`/backend/api/ganhos/read.php?usuario_id=${usuarioId}`);
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao listar ganhos:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
