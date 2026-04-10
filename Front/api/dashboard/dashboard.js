// Dashboard - Busca dados financeiros do dashboard
async function carregarDashboard() {
    const resultado = await fetch('../backend/api/dashboard/dados.php');
    return await resultado.json();
}
