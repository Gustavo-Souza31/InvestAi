async function listarDespesas(usuarioId) {
    try {
        const res = await fetch(`../backend/api/despesas/read.php?usuario_id=${usuarioId}`);
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao listar despesas:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
