/**
 * dashboard/render.js — Renderização e lógica da página de dashboard
 *
 * Responsável por:
 * - Carregar dados do dashboard
 * - Renderizar informações financeiras
 * - Inicializar a página
 *
 * Funções compartilhadas em shared.js:
 * - formatMoney() - Formata valores em moeda brasileira
 */


// Carrega dados e renderiza dashboard
async function inicializar() {
    try {
        const json = await carregarDashboard();
        
        if (json.status === 'success') {
            // Dados financeiros
            document.getElementById('saldo-inicial').textContent = formatMoney(json.financeiro.saldo_inicial);
            document.getElementById('saldo-atual').textContent = formatMoney(json.financeiro.saldo_atual);
            document.getElementById('renda-mensal').textContent = formatMoney(json.financeiro.renda_mensal);
            document.getElementById('total-ganhos').textContent = formatMoney(json.financeiro.total_ganhos);
            document.getElementById('total-despesas').textContent = formatMoney(json.financeiro.total_despesas);
            document.getElementById('objetivo').textContent = json.financeiro.objetivo_financeiro;

            // Mostrar dados
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').style.display = 'block';
        } else {
            document.getElementById('loading').innerHTML = '<p class="text-danger">Erro ao carregar dados</p>';
        }
    } catch (error) {
        console.error('Erro ao inicializar dashboard:', error);
        document.getElementById('loading').innerHTML = '<p class="text-danger">Erro ao carregar dados</p>';
    }
}


// Inicialize quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', inicializar);
