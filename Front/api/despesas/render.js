/**
 * despesas/render.js — Renderização e lógica da página de despesas
 *
 * Responsável por:
 * - Carregar e renderizar lista de despesas
 * - Abrir modais de edição e exclusão
 * - Inicializar a página
 *
 * Funções compartilhadas em shared.js:
 * - formatMoney(), formatDate(), escapeHtml()
 * - openEdit(), openDelete()
 */

// Carrega e renderiza todas as despesas
async function carregarDespesas() {
    const res = await listarDespesas(USUARIO_ID);
    const container = document.getElementById('despesas-container');

    if (res.status !== 'success' || !res.despesas || res.despesas.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>Nenhuma despesa registrada ainda. Comece adicionando acima!</p>
            </div>`;
        document.getElementById('badge-count').textContent    = '0';
        document.getElementById('total-registros').textContent = '0';
        document.getElementById('total-mes').textContent       = 'R$ 0,00';
        document.getElementById('total-fixos').textContent     = 'R$ 0,00';
        return;
    }

    const despesas = res.despesas;
    const agora    = new Date();
    let totalMes   = 0;
    let totalFixos = 0;

    despesas.forEach(d => {
        const dt = new Date(d.data_despesa);
        if (dt.getMonth() === agora.getMonth() && dt.getFullYear() === agora.getFullYear()) {
            totalMes += parseFloat(d.valor);
        }
        if (parseInt(d.fixo) === 1) {
            totalFixos += parseFloat(d.valor);
        }
    });

    document.getElementById('total-mes').textContent       = formatMoney(totalMes);
    document.getElementById('total-fixos').textContent     = formatMoney(totalFixos);
    document.getElementById('total-registros').textContent = despesas.length;
    document.getElementById('badge-count').textContent     = despesas.length;

    let html = '';
    despesas.forEach(d => {
        const fixoBadge = parseInt(d.fixo) === 1
            ? '<span class="item-meta-badge">FIXO</span>'
            : '';
        html += `
            <div class="list-item">
                <div class="item-icon"><i class="bi bi-arrow-up-right"></i></div>
                <div class="item-info">
                    <div class="desc">${escapeHtml(d.descricao)}</div>
                    <div class="meta">
                        <span><i class="bi bi-calendar3 me-1"></i>${formatDate(d.data_despesa)}</span>
                        ${fixoBadge}
                    </div>
                </div>
                <div class="item-value">- ${formatMoney(d.valor)}</div>
                <div class="item-actions">
                    <button class="btn-edit" title="Editar" onclick="openEdit(${d.id}, '${escapeHtml(d.descricao)}', ${d.valor}, '${d.data_despesa}', ${d.fixo})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-delete" title="Excluir" onclick="openDelete(${d.id})">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>`;
    });
    container.innerHTML = html;
}

// Abre modal de edição
function abrirEdicao(id, descricao, valor, data, fixo) {
    openEdit(id, descricao, valor, data, fixo);
}

// Abre modal de exclusão
function abrirDelecao(id) {
    openDelete(id);
}

// Inicializa a página
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('despesa-data').value = new Date().toISOString().split('T')[0];
    carregarDespesas();
});
