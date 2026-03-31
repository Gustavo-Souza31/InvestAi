async function excluirGanho(id) {
    try {
        const res = await fetch('/backend/api/ganhos/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const json = await res.json();
        return json;
    } catch (error) {
        console.error('Erro ao excluir ganho:', error);
        return { status: 'error', message: 'Erro de conexão.' };
    }
}
