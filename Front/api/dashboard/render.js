async function inicializar(periodo = '3m', isUpdate = false) {
    const loadingEl = document.getElementById('loading');
    const contentEl = document.getElementById('content');

    // Se não for update, mostra tela cheia de loading
    if (!isUpdate) {
        contentEl.style.display = 'none';
        loadingEl.style.display = 'flex';
    } else {
        // Coloca os valores em loading text para experiência mais fluida
        const cardsValues = ['saldo-inicial', 'saldo-atual', 'renda-mensal', 'total-ganhos', 'total-despesas'];
        cardsValues.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span>';
        });
    }

    try {
        // Carrega dados do dashboard
        const resultado = await carregarDashboard(periodo);

        // Se sucesso, renderiza dados
        if (resultado.status === 'success') {

            // Helper para atualizar texto apenas se elemento existir
            const updateEl = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.textContent = text;
            };

            // Atualiza valores finaceiros
            updateEl('saldo-inicial', formatMoney(resultado.financeiro.saldo_inicial));
            updateEl('saldo-atual', formatMoney(resultado.financeiro.saldo_atual));
            updateEl('renda-mensal', formatMoney(resultado.financeiro.renda_mensal));
            updateEl('total-ganhos', formatMoney(resultado.financeiro.total_ganhos));
            updateEl('total-despesas', formatMoney(resultado.financeiro.total_despesas));
            updateEl('objetivo', resultado.financeiro.objetivo_financeiro);

            // Esconde loading e mostra conteúdo apenas se for loading inicial
            if (!isUpdate) {
                loadingEl.style.display = 'none';
                contentEl.style.display = 'block';
            }
        } else {
            // Se erro, mostra mensagem
            mostrarErro(isUpdate, loadingEl, contentEl);
        }
    } catch (error) {
        // Erro de conexão
        console.error('Erro ao inicializar dashboard:', error);
        mostrarErro(isUpdate, loadingEl, contentEl);
    }
}

function mostrarErro(isUpdate, loadingEl, contentEl) {
    const msg = "Não foi possível carregar os dados financeiros do período selecionado. Por favor, tente novamente.";
    if (!isUpdate) {
        contentEl.style.display = 'none';
        loadingEl.style.display = 'flex';
        loadingEl.innerHTML = `<p class="text-danger">${msg}</p>`;
    } else {
        alert(msg);
        // Restaura valores para R$ 0,00 caso não carregue
        const cardsValues = ['saldo-inicial', 'saldo-atual', 'renda-mensal', 'total-ganhos', 'total-despesas'];
        cardsValues.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = 'R$ 0,00';
        });
    }
}

// Listener do DOM
document.addEventListener('DOMContentLoaded', () => {
    const defaultPeriodo = window.DEFAULT_PERIODO || '3m';
    inicializar(defaultPeriodo, false);
});
