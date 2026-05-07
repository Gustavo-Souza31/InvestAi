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

            // Atualiza valores financeiros
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

                // Carregar sugestões de economia (mês/ano atual)
                const hoje = new Date();
                const mesAtual = hoje.getMonth() + 1;
                const anoAtual = hoje.getFullYear();
                if (window.sugestoesAPI) {
                    window.sugestoesAPI.inicializar(mesAtual, anoAtual);
                }
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

function mostrarEstadoVazio(loadingEl, contentEl) {
    contentEl.style.display = 'none';
    loadingEl.style.display = 'flex';
    loadingEl.innerHTML = `
        <div style="text-align: center; padding: 60px 20px; max-width: 500px; margin: 0 auto;">
            <div style="
                width: 88px; height: 88px; border-radius: 50%;
                background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.15));
                border: 1px solid rgba(99,102,241,0.3);
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 28px; font-size: 2.2rem;
            ">💰</div>
            <h3 style="color: #fff; font-size: 1.45rem; font-weight: 700; margin-bottom: 12px;">
                Bem-vindo ao InvestAI!
            </h3>
            <p style="color: #888; font-size: 0.95rem; line-height: 1.7; margin-bottom: 36px;">
                Você ainda não tem dados financeiros cadastrados.<br>
                Comece adicionando seus ganhos e despesas para ver<br>seu resumo e análises aqui.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="ganhos.php" style="
                    display: inline-flex; align-items: center; gap: 8px;
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    color: #fff; text-decoration: none;
                    padding: 12px 22px; border-radius: 10px;
                    font-weight: 600; font-size: 0.9rem; transition: opacity .2s;
                " onmouseover="this.style.opacity='.82'" onmouseout="this.style.opacity='1'">
                    <i class="bi bi-plus-circle"></i> Adicionar Ganho
                </a>
                <a href="despesas.php" style="
                    display: inline-flex; align-items: center; gap: 8px;
                    background: linear-gradient(135deg, #ef4444, #dc2626);
                    color: #fff; text-decoration: none;
                    padding: 12px 22px; border-radius: 10px;
                    font-weight: 600; font-size: 0.9rem; transition: opacity .2s;
                " onmouseover="this.style.opacity='.82'" onmouseout="this.style.opacity='1'">
                    <i class="bi bi-dash-circle"></i> Adicionar Despesa
                </a>
                <a href="perfil.php" style="
                    display: inline-flex; align-items: center; gap: 8px;
                    background: rgba(255,255,255,0.07);
                    border: 1px solid rgba(255,255,255,0.12);
                    color: #bbb; text-decoration: none;
                    padding: 12px 22px; border-radius: 10px;
                    font-weight: 600; font-size: 0.9rem; transition: opacity .2s;
                " onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                    <i class="bi bi-person-gear"></i> Completar Perfil
                </a>
            </div>
        </div>
    `;
}

function mostrarErro(isUpdate, loadingEl, contentEl) {
    const msg = "Não foi possível carregar os dados financeiros do período selecionado. Por favor, tente novamente.";
    if (!isUpdate) {
        contentEl.style.display = 'none';
        loadingEl.style.display = 'flex';
        loadingEl.innerHTML = `
            <div class="text-center p-5">
                <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: #dc3545;"></i>
                <h3 class="mt-3">Ops! Algo deu errado</h3>
                <p class="text-muted">${msg}</p>
                <button onclick="location.reload()" class="btn btn-outline-primary mt-2">Tentar novamente</button>
            </div>
        `;
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
