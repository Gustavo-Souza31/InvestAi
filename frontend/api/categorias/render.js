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

        <div class="mb-3 d-flex gap-2">
            <input type="text" id="nova-categoria-nome" class="form-control" placeholder="Nome da nova categoria...">
            <button class="btn btn-primary" onclick="adicionarCategoria()">
                <i class="bi bi-plus-lg"></i> Adicionar
            </button>
        </div>

        <div class="list-container mt-4" style="max-height: 300px; overflow-y: auto;">
            <div id="lista-categorias-gerenciar"></div>
        </div>

        <div class="d-flex gap-3 justify-content-end mt-4">
            <button type="button" class="btn-cancel" onclick="closeModal('modal-categorias')">Fechar</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-edit-categoria">
    <div class="modal-card" style="max-width: 400px;">
        <h2><i class="bi bi-pencil"></i> Editar Categoria</h2>

        <div class="mb-4 mt-3">
            <label class="form-label">NOVO NOME</label>
            <input type="text" id="edit-categoria-nome" class="form-control" placeholder="Ex: Roupas, Mercado..." required>
            <input type="hidden" id="edit-categoria-id">
        </div>

        <div class="d-flex gap-3 justify-content-end">
            <button type="button" class="btn-cancel" onclick="closeModal('modal-edit-categoria')">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="salvarEdicaoCategoria()">Salvar</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-delete-categoria">
    <div class="confirm-card">
        <div class="icon-danger">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h3>Excluir Categoria?</h3>
        <p>Deseja realmente excluir esta categoria? As transações associadas a ela ficarão sem categoria.</p>
        <input type="hidden" id="delete-categoria-id">
        <div class="d-flex justify-content-center gap-3">
            <button class="btn-cancel" onclick="closeModal('modal-delete-categoria')">Cancelar</button>
            <button class="btn-danger" onclick="confirmarExclusaoCategoria()">Excluir</button>
        </div>
    </div>
</div>
`;

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('modal-categorias')) {
        document.body.insertAdjacentHTML('beforeend', modalCategoriasHTML);
    }
});

function openCategoriasModal() {
    const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';
    const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
    carregarCategorias(tipo, selectIds);
    renderizarListaGerenciarCategorias();
    openModal('modal-categorias');
}

function renderizarListaGerenciarCategorias() {
    const container = document.getElementById('lista-categorias-gerenciar');

    if (categoriasAtuais.length === 0) {
        container.innerHTML = `<p class="text-muted text-center my-3">Nenhuma categoria criada ainda.</p>`;
        return;
    }

    container.innerHTML = categoriasAtuais.map(cat => `
        <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
            <span>${escapeHtml(cat.nome)}</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="editarCategoriaNome(${cat.id}, '${escapeHtml(cat.nome)}')" title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="excluirCategoria(${cat.id})" title="Excluir">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>`
    ).join('');
}
