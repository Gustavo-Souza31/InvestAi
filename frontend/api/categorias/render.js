// ===== CONSTANTES =====

const CATEGORY_ICONS = {
    'Salário':                '💰',
    'Freelance':              '🎯',
    'Investimentos':          '📈',
    'Alimentação':            '🍽️',
    'Transporte':             '🚗',
    'Habitação':              '🏠',
    'Saúde':                  '💊',
    'Educação':               '📚',
    'Entretenimento':         '🎬',
    'Vestuário e Acessórios': '👕',
    'Utilidades Domésticas':  '💡',
    'Outros Gastos':          '📦',
};

const modalCategoriasHTML = `
<div class="modal-overlay" id="modal-categorias">
    <div class="modal-card" style="max-width: 500px;">
        <h2><i class="bi bi-tags"></i> Gerenciar Categorias</h2>

        <div class="cat-add-row">
            <input type="text" id="nova-categoria-nome" class="form-control" placeholder="Nome da nova categoria...">
            <button class="btn-save" onclick="criarCategoria()">
                <i class="bi bi-plus-lg"></i> Adicionar
            </button>
        </div>

        <div class="cat-lista-wrap">
            <div id="lista-categorias-gerenciar"></div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:16px;">
            <button type="button" class="btn-cancel" onclick="closeModal('modal-categorias')">Fechar</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-edit-categoria">
    <div class="modal-card" style="max-width: 400px;">
        <h2><i class="bi bi-pencil"></i> Editar Categoria</h2>
        <input type="hidden" id="edit-categoria-id">
        <div style="margin:16px 0 24px;">
            <input type="text" id="edit-categoria-nome" class="form-control" placeholder="Ex: Roupas, Mercado..." required>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" class="btn-cancel" onclick="closeModal('modal-edit-categoria')">Cancelar</button>
            <button type="button" class="btn-save" onclick="atualizarCategoria()">Salvar</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-delete-categoria">
    <div class="confirm-card">
        <div class="icon-danger">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h3>Excluir Categoria?</h3>
        <p>As transações associadas ficarão sem categoria.</p>
        <input type="hidden" id="delete-categoria-id">
        <div style="display:flex;justify-content:center;gap:12px;">
            <button class="btn-cancel" onclick="closeModal('modal-delete-categoria')">Cancelar</button>
            <button class="btn-danger" onclick="confirmarExclusaoCategoria()">Excluir</button>
        </div>
    </div>
</div>
`;

// ===== RENDER =====

function renderizarListaGerenciarCategorias() {
    const container = document.getElementById('lista-categorias-gerenciar');

    if (categoriasAtuais.length === 0) {
        container.innerHTML = `<p class="text-muted text-center my-3">Nenhuma categoria criada ainda.</p>`;
        return;
    }

    container.innerHTML = categoriasAtuais.map(cat => `
        <div class="cat-item">
            <div class="cat-item-icon"><i class="bi bi-tag-fill"></i></div>
            <span class="cat-item-nome">${escapeHtml(cat.nome)}</span>
            <div class="cat-item-actions">
                <button class="cat-edit-btn" onclick="editarCategoriaNome(${cat.id}, '${escapeHtml(cat.nome)}')" title="Editar">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="cat-delete-btn" onclick="excluirCategoria(${cat.id})" title="Excluir">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>`
    ).join('');
}

// ===== LOAD =====

function openCategoriasModal() {
    const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';
    const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
    carregarCategorias(tipo, selectIds);
    renderizarListaGerenciarCategorias();
    openModal('modal-categorias');
}

// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('modal-categorias')) {
        document.body.insertAdjacentHTML('beforeend', modalCategoriasHTML);
    }
});
