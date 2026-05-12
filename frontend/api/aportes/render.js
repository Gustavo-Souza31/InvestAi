// ===== CONSTANTES =====

const modalAportesHTML = `
<div class="modal-overlay" id="modal-edit-aporte">
    <div class="modal-card" style="max-width:400px;">
        <h2><i class="bi bi-pencil"></i> Editar Aporte</h2>
        <input type="hidden" id="edit-aporte-id">
        <div class="mb-3 mt-3">
            <label class="form-label">VALOR (R$)</label>
            <input type="number" id="edit-aporte-valor" class="form-control" min="0.01" step="0.01" placeholder="Ex: 50.00">
        </div>
        <div class="mb-4">
            <label class="form-label">DATA</label>
            <input type="date" id="edit-aporte-data" class="form-control">
        </div>
        <div class="d-flex gap-3 justify-content-end">
            <button type="button" class="btn-cancel" onclick="closeModal('modal-edit-aporte')">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="atualizarAporte()">Salvar</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-delete-aporte">
    <div class="confirm-card">
        <div class="icon-danger">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h3>Excluir Aporte?</h3>
        <p>O valor será revertido no progresso da meta.</p>
        <input type="hidden" id="delete-aporte-id">
        <div class="d-flex justify-content-center gap-3">
            <button class="btn-cancel" onclick="closeModal('modal-delete-aporte')">Cancelar</button>
            <button class="btn-danger" onclick="excluirAporte()">Excluir</button>
        </div>
    </div>
</div>
`;

// ===== RENDER =====

function renderizarAportes(aportes) {
    const lista = document.getElementById('aportes-lista');
    if (!lista) return;

    if (!aportes || aportes.length === 0) {
        lista.innerHTML = '<p style="padding:8px 0;font-size:0.85rem;color:var(--color-text-muted);">Nenhum aporte registrado.</p>';
        return;
    }

    lista.innerHTML = aportes.map(a => {
        const dataFormatada = formatDate(a.data_aporte);
        const valorFormatado = formatMoney(parseFloat(a.valor));
        return `
        <div class="aporte-item" id="aporte-item-${a.id}">
            <span class="aporte-item-data">${dataFormatada}</span>
            <span class="aporte-item-valor">${valorFormatado}</span>
            <div class="aporte-item-actions">
                <button class="orc-edit-btn" title="Editar" onclick="abrirEdicaoAporte(${a.id}, ${a.valor}, '${a.data_aporte}')">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="orc-delete-btn" title="Excluir" onclick="abrirExclusaoAporte(${a.id})">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

// ===== LOAD =====

async function carregarAportes(metaId) {
    const lista = document.getElementById('aportes-lista');
    if (!lista) return;

    lista.innerHTML = '<p style="padding:8px 0;font-size:0.85rem;color:var(--color-text-muted);">Carregando...</p>';

    try {
        const resultado = await listarAportes(metaId);
        if (resultado.status !== 'success') {
            lista.innerHTML = '';
            return;
        }
        renderizarAportes(resultado.aportes);
    } catch (error) {
        console.error('Erro ao carregar aportes:', error);
        lista.innerHTML = '';
    }
}

// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('modal-edit-aporte')) {
        document.body.insertAdjacentHTML('beforeend', modalAportesHTML);
    }
});
