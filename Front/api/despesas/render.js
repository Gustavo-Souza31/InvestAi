// ===== PROCESSAMENTO =====

function processarDespesas(despesas) {
    const agora = new Date();
    let totalMes = 0;
    let totalFixos = 0;

    despesas.forEach(despesa => {
        const data = new Date(despesa.data_despesa);
        if (data.getMonth() === agora.getMonth() && data.getFullYear() === agora.getFullYear()) {
            totalMes += parseFloat(despesa.valor);
        }
        if (parseInt(despesa.fixo) === 1) {
            totalFixos += parseFloat(despesa.valor);
        }
    });

    return {
        totalMes,
        totalFixos,
        count: despesas.length
    };
}

// ===== RENDER =====

function gerarHTMLItemDespesa(despesa) {
    const fixoBadge = parseInt(despesa.fixo) === 1
        ? '<span class="item-meta-badge">FIXO</span>'
        : '';

    return `
        <div class="list-item">
            <div class="item-icon"><i class="bi bi-arrow-up-right"></i></div>
            <div class="item-info">
                <div class="desc">${escapeHtml(despesa.descricao)}</div>


                <!-- ✅ EXEMPLO DE NOVA LINHA - Para adicionar um novo campo: -->
                <!-- <div class="categoria">${escapeHtml(despesa.categoria)}</div> -->


                
                <div class="meta">
                    <span><i class="bi bi-calendar3 me-1"></i>${formatDate(despesa.data_despesa)}</span>
                    ${fixoBadge}
                </div>
            </div>
            <div class="item-value">- ${formatMoney(despesa.valor)}</div>
            <div class="item-actions">
                <button class="btn-edit" title="Editar" onclick="openEdit(${despesa.id}, '${escapeHtml(despesa.descricao)}', ${despesa.valor}, '${despesa.data_despesa}', ${despesa.fixo})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-delete" title="Excluir" onclick="openDelete(${despesa.id})">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>`;
}

function renderizarListaDespesas(despesas) {
    const container = document.getElementById('despesas-container');
    const html = despesas.map(despesa => gerarHTMLItemDespesa(despesa)).join('');
    container.innerHTML = html;
}

function renderizarEstadoVazio() {
    const container = document.getElementById('despesas-container');
    container.innerHTML = `
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Nenhuma despesa registrada ainda. Comece adicionando acima!</p>
        </div>`;

    atualizarResumo({
        totalMes: 0,
        totalFixos: 0,
        count: 0
    });
}

function atualizarResumo(totals) {
    document.getElementById('total-mes').textContent = formatMoney(totals.totalMes);
    document.getElementById('total-fixos').textContent = formatMoney(totals.totalFixos);
    document.getElementById('total-registros').textContent = totals.count;
    document.getElementById('badge-count').textContent = totals.count;
}

// ===== LOAD =====

async function carregarDespesas() {
    try {
        const resultado = await listarDespesas(USUARIO_ID);

        if (resultado.status !== 'success' || !resultado.despesas || resultado.despesas.length === 0) {
            renderizarEstadoVazio();
            return;
        }

        const totals = processarDespesas(resultado.despesas);
        renderizarListaDespesas(resultado.despesas);
        atualizarResumo(totals);

    } catch (error) {
        console.error('Erro ao carregar despesas:', error);
        showAlert('Erro ao carregar despesas. Tente novamente.', 'error');
    }
}

// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('despesa-data').value = new Date().toISOString().split('T')[0];
    carregarDespesas();
});
