// Dashboard - Busca dados financeiros do dashboard
async function carregarDashboard(periodo = '3m') {
    const resultado = await fetch(`/inventai/backend/api/dashboard/dados.php?periodo=${periodo}`);
    return await resultado.json();
}
