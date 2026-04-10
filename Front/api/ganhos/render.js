// ===== PROCESSAMENTO =====

function processarGanhos(ganhos) {
    const agora = new Date();
    let totalMes = 0;
    let totalFixos = 0;

    ganhos.forEach(ganho => {
        const data = new Date(ganho.data_ganho);
        if (data.getMonth() === agora.getMonth() && data.getFullYear() === agora.getFullYear()) {
            totalMes += parseFloat(ganho.valor);
        }
        if (parseInt(ganho.fixo) === 1) {
            totalFixos += parseFloat(ganho.valor);
        }
    });

    return {
        totalMes,
        totalFixos,
        count: ganhos.length
    };
}

// ===== RENDER =====

function gerarHTMLItemGanho(ganho) {
    const fixoBadge = parseInt(ganho.fixo) === 1
        ? '<span class="item-meta-badge">FIXO</span>'
        : '';

    return `
        <div class="list-item">
            <div class="item-icon"><i class="bi bi-arrow-down-left"></i></div>
            <div class="item-info">
                <div class="desc">${escapeHtml(ganho.descricao)}</div>



                <!-- ✅ EXEMPLO DE NOVA LINHA - Para adicionar um novo campo: -->
                <!-- <div class="categoria">${escapeHtml(ganho.categoria)}</div> -->

                

                <div class="meta">
                    <span><i class="bi bi-calendar3 me-1"></i>${formatDate(ganho.data_ganho)}</span>
                    ${fixoBadge}
                </div>
            </div>
            <div class="item-value">+ ${formatMoney(ganho.valor)}</div>
            <div class="item-actions">
                <button class="btn-edit" title="Editar" onclick="openEdit(${ganho.id}, '${escapeHtml(ganho.descricao)}', ${ganho.valor}, '${ganho.data_ganho}', ${ganho.fixo})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-delete" title="Excluir" onclick="openDelete(${ganho.id})">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>`;
}

function renderizarListaGanhos(ganhos) {
    const container = document.getElementById('ganhos-container');
    const html = ganhos.map(ganho => gerarHTMLItemGanho(ganho)).join('');
    container.innerHTML = html;
}

function renderizarEstadoVazio() {
    const container = document.getElementById('ganhos-container');
    container.innerHTML = `
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Nenhum ganho registrado ainda. Comece adicionando acima!</p>
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

async function carregarGanhos() {
    try {
        const resultado = await listarGanhos(USUARIO_ID);

        if (resultado.status !== 'success' || !resultado.ganhos || resultado.ganhos.length === 0) {
            renderizarEstadoVazio();
            return;
        }

        const totals = processarGanhos(resultado.ganhos);
        renderizarListaGanhos(resultado.ganhos);
        atualizarResumo(totals);

    } catch (error) {
        console.error('Erro ao carregar ganhos:', error);
        showAlert('Erro ao carregar ganhos. Tente novamente.', 'error');
    }
}

// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('ganho-data').value = new Date().toISOString().split('T')[0];
    carregarGanhos();
});
