/**
 * ganhos/render.js — Renderização e lógica da página de ganhos
 *
 * Responsável por:
 * - Carregar e renderizar lista de ganhos
 * - Abrir modais de edição e exclusão
 * - Inicializar a página
 *
 * Funções compartilhadas em shared.js:
 * - formatMoney(), formatDate(), escapeHtml()
 * - openEdit(), openDelete()
 */

// Carrega e renderiza todos os ganhos
async function carregarGanhos() {
    const res = await listarGanhos(USUARIO_ID);
    const container = document.getElementById('ganhos-container');

    if (res.status !== 'success' || !res.ganhos || res.ganhos.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>Nenhum ganho registrado ainda. Comece adicionando acima!</p>
            </div>`;
        document.getElementById('badge-count').textContent    = '0';
        document.getElementById('total-registros').textContent = '0';
        document.getElementById('total-mes').textContent       = 'R$ 0,00';
        document.getElementById('total-fixos').textContent     = 'R$ 0,00';
        return;
    }

    const ganhos = res.ganhos;
    const agora  = new Date();
    let totalMes = 0;
    let totalFixos = 0;

    ganhos.forEach(g => {
        const dt = new Date(g.data_ganho);
        if (dt.getMonth() === agora.getMonth() && dt.getFullYear() === agora.getFullYear()) {
            totalMes += parseFloat(g.valor);
        }
        if (parseInt(g.fixo) === 1) {
            totalFixos += parseFloat(g.valor);
        }
    });

    document.getElementById('total-mes').textContent       = formatMoney(totalMes);
    document.getElementById('total-fixos').textContent     = formatMoney(totalFixos);
    document.getElementById('total-registros').textContent = ganhos.length;
    document.getElementById('badge-count').textContent     = ganhos.length;

    let html = '';
    ganhos.forEach(g => {
        const fixoBadge = parseInt(g.fixo) === 1
            ? '<span class="item-meta-badge">FIXO</span>'
            : '';
        html += `
            <div class="list-item">
                <div class="item-icon"><i class="bi bi-arrow-down-left"></i></div>
                <div class="item-info">
                    <div class="desc">${escapeHtml(g.descricao)}</div>
                    <div class="meta">
                        <span><i class="bi bi-calendar3 me-1"></i>${formatDate(g.data_ganho)}</span>
                        ${fixoBadge}
                    </div>
                </div>
                <div class="item-value">+ ${formatMoney(g.valor)}</div>
                <div class="item-actions">
                    <button class="btn-edit" title="Editar" onclick="openEdit(${g.id}, '${escapeHtml(g.descricao)}', ${g.valor}, '${g.data_ganho}', ${g.fixo})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-delete" title="Excluir" onclick="openDelete(${g.id})">
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
    document.getElementById('ganho-data').value = new Date().toISOString().split('T')[0];
    carregarGanhos();
});
