async function inicializar() {

    try {
        // Carrega dados do dashboard
        const resultado = await carregarDashboard();
        
        // Se sucesso, renderiza dados
        if (resultado.status === 'success') {
            
            // Atualiza valores finaceiros
            document.getElementById('saldo-inicial').textContent = formatMoney(resultado.financeiro.saldo_inicial);
            document.getElementById('saldo-atual').textContent = formatMoney(resultado.financeiro.saldo_atual);
            document.getElementById('renda-mensal').textContent = formatMoney(resultado.financeiro.renda_mensal);
            document.getElementById('total-ganhos').textContent = formatMoney(resultado.financeiro.total_ganhos);
            document.getElementById('total-despesas').textContent = formatMoney(resultado.financeiro.total_despesas);
            document.getElementById('objetivo').textContent = resultado.financeiro.objetivo_financeiro;

            // ✅ EXEMPLO DE NOVO CAMPO - Para adicionar um novo valor financeiro:
            // document.getElementById('novo-campo').textContent = formatMoney(resultado.financeiro.novo_campo);

            // Esconde loading e mostra conteúdo
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').style.display = 'block';
        } else {
            // Se erro, mostra mensagem
            document.getElementById('loading').innerHTML = '<p class="text-danger">Erro ao carregar dados</p>';
        }
    } catch (error) {
        // Erro de conexão
        console.error('Erro ao inicializar dashboard:', error);
        document.getElementById('loading').innerHTML = '<p class="text-danger">Erro ao carregar dados</p>';
    }
}

// Listener do DOM
document.addEventListener('DOMContentLoaded', inicializar);
