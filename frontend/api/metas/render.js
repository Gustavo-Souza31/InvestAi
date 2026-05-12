// ===== CONSTANTES =====

// Modo atual do modal: 'create' para novo, 'edit' para edição
let _metaModo = 'create';

// ===== RENDER =====

function formatMoneyBR(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function renderizarMetas(metas) {
    const grid = document.getElementById('metas-grid');
    const empty = document.getElementById('metas-empty');

    if (!metas || metas.length === 0) {
        empty.style.display = 'flex';
        grid.querySelectorAll('.orc-card').forEach(c => c.remove());
        return;
    }

    empty.style.display = 'none';
    grid.querySelectorAll('.orc-card').forEach(c => c.remove());

    metas.forEach(m => {
        const guardado = parseFloat(m.valor_guardado || 0);
        const total = parseFloat(m.valor_total || 1);
        const pct = Math.min(100, Math.round((guardado / total) * 100));
        const prazo = m.prazo ? new Date(m.prazo).toLocaleDateString() : 'Sem prazo';

        const card = document.createElement('div');
        card.className = 'orc-card';
        card.innerHTML = `
            <div class="orc-card-top">
                <span class="orc-card-icon">🏁</span>
                <div class="orc-card-info">
                    <span class="orc-card-nome">${escapeHtml(m.nome)}</span>
                    <span class="orc-card-status">Prazo: ${prazo}</span>
                </div>
                <div class="orc-card-buttons">
                    <button class="orc-edit-btn" onclick="abrirModalAporte(${m.id}, '${escapeHtmlAttr(m.nome)}')" title="Aportar">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                    <button class="orc-edit-btn" onclick="abrirModalMetaEdicao(${m.id}, '${escapeHtmlAttr(m.nome)}', ${m.valor_total}, '${m.prazo || ''}')" title="Editar">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="orc-delete-btn" onclick="abrirExclusaoMeta(${m.id}, '${escapeHtmlAttr(m.nome)}')" title="Remover">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>
            <div class="orc-progress-wrap">
                <div class="orc-progress-bar" style="width:${pct}%; background: ${pct >= 100 ? 'var(--color-expense)' : pct >= 80 ? '#fbbf24' : 'var(--color-gain)'};"></div>
            </div>
            <div class="orc-card-valores">
                <span>${formatMoneyBR(guardado)} guardado</span>
                <span>${pct}% de <strong>${formatMoneyBR(total)}</strong></span>
            </div>
        `;
        grid.appendChild(card);
    });
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"'`]/g, function (s) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'})[s]; });
}

function escapeHtmlAttr(str) {
    return escapeHtml(str).replace(/'/g, "\'");
}

// ===== LOAD =====

async function carregarMetas() {
    try {
        const resultado = await listarMetas();
        if (resultado.status === 'success') {
            renderizarMetas(resultado.metas);
        }
    } catch (error) {
        console.error('Erro ao carregar metas:', error);
    }
}

// ===== MODAL =====

function abrirModalMeta() {
    _metaModo = 'create';
    document.getElementById('meta-id').value = '';
    document.getElementById('meta-nome').value = '';
    document.getElementById('meta-valor').value = '';
    document.getElementById('meta-prazo').value = '';
    ocultarAlertMeta();
    document.getElementById('meta-overlay').classList.add('active');
    document.getElementById('meta-nome').focus();
}

function abrirModalMetaEdicao(id, nome, valorTotal, prazo) {
    _metaModo = 'edit';
    document.getElementById('meta-id').value = id;
    document.getElementById('meta-nome').value = nome;
    document.getElementById('meta-valor').value = valorTotal;
    document.getElementById('meta-prazo').value = prazo || '';
    ocultarAlertMeta();
    document.getElementById('meta-overlay').classList.add('active');
    document.getElementById('meta-nome').focus();
}

function fecharModalMeta() {
    document.getElementById('meta-overlay').classList.remove('active');
}

// Ponto único chamado pelo onclick do botão Salvar no HTML
function salvarMeta() {
    if (_metaModo === 'edit') {
        atualizarMeta();
    } else {
        criarMeta();
    }
}

// ===== ALERT INTERNO DO MODAL =====

function mostrarAlertMeta(msg, tipo) {
    const el = document.getElementById('meta-alert');
    if (!el) return; // Proteção se elemento não existir
    el.textContent = msg;
    el.className = `meta-alert meta-alert-${tipo}`;
    el.style.display = 'block';
}

function ocultarAlertMeta() {
    const el = document.getElementById('meta-alert');
    if (el) el.style.display = 'none';
}

// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('meta-overlay')?.addEventListener('click', e => {
        if (e.target.id === 'meta-overlay') fecharModalMeta();
    });

    document.getElementById('aporte-overlay')?.addEventListener('click', e => {
        if (e.target.id === 'aporte-overlay') fecharModalAporte();
    });

    document.getElementById('meta-modal-delete')?.addEventListener('click', e => {
        if (e.target.id === 'meta-modal-delete') fecharExclusaoMeta();
    });

    carregarMetas();
});

