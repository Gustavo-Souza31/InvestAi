// Dashboard - GET dados
async function carregarDashboard() {
    const res = await fetch('/inventai/backend/api/dashboard/dados.php');
    return await res.json();
}
