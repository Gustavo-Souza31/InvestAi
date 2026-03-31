// Busca o resumo de ganhos e despesas por período

async function calcularResumo(usuarioId, periodo = 'mensal') {
    const url = `../backend/api/resumo/periodo.php?usuario_id=${usuarioId}&periodo=${periodo}`;
    const response = await fetch(url);
    const data = await response.json();
    return data;
}
