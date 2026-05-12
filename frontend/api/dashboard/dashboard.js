async function carregarDashboard(periodo = '3m') {
    const resposta = await fetch(`${BASE_PATH}/backend/api/dashboard/dados.php?periodo=${periodo}`);
    return await resposta.json();
}
