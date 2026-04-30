// script para gerenciar categorias no frontend
let categoriasAtuais = [];
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
            <div id="lista-categorias-gerenciar">
                <!-- Categorias serão listadas aqui -->
            </div>
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

// Injeta o modal no body
document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('modal-categorias')) {
        document.body.insertAdjacentHTML('beforeend', modalCategoriasHTML);
    }
});

function openCategoriasModal() {
    renderizarListaGerenciarCategorias();
    openModal('modal-categorias');
}

function renderizarListaGerenciarCategorias() {
    const container = document.getElementById('lista-categorias-gerenciar');
    if (categoriasAtuais.length === 0) {
        container.innerHTML = `<p class="text-muted text-center my-3">Nenhuma categoria encontrada.</p>`;
        return;
    }

    container.innerHTML = categoriasAtuais.map(cat => {
        let actionBtns = '';
        if (cat.is_custom) {
            actionBtns = `
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="editarCategoriaNome(${cat.id}, '${escapeHtml(cat.nome)}')" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="excluirCategoria(${cat.id})" title="Excluir">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
        } else {
            actionBtns = `<span class="badge bg-secondary">Padrão</span>`;
        }

        return `
        <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
            <span>${escapeHtml(cat.nome)}</span>
            ${actionBtns}
        </div>`;
    }).join('');
}

async function carregarCategorias(tipo, selectIds) {
    try {
        const response = await fetch(`../backend/api/categorias/read.php?tipo=${tipo}`);
        const data = await response.json();

        if (data.status === 'success') {
            categoriasAtuais = data.categorias;

            // Preencher os selects especificados
            selectIds.forEach(id => {
                const select = document.getElementById(id);
                if (select) {
                    const valueAntigo = select.value;
                    select.innerHTML = '<option value="">Selecione uma categoria...</option>' +
                        categoriasAtuais.map(cat => `<option value="${cat.id}">${escapeHtml(cat.nome)}</option>`).join('');

                    // Tentar manter o valor selecionado se ainda existir
                    if (valueAntigo && categoriasAtuais.find(c => c.id == valueAntigo)) {
                        select.value = valueAntigo;
                    }
                }
            });

            // Atualizar modal se estiver aberto
            if (document.getElementById('modal-categorias').classList.contains('show')) {
                renderizarListaGerenciarCategorias();
            }
        } else {
            throw new Error(data.message || 'Erro desconhecido ao carregar categorias.');
        }
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">Erro ao carregar categorias</option>';
            }
        });
    }
}

async function adicionarCategoria() {
    const input = document.getElementById('nova-categoria-nome');
    const nome = input.value.trim();
    const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';

    if (!nome) {
        showAlert('Digite o nome da categoria.', 'error');
        return;
    }

    try {
        const response = await fetch('../backend/api/categorias/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome, tipo })
        });

        const data = await response.json();
        if (data.status === 'success') {
            input.value = '';
            showAlert(data.message, 'success');
            // Recarrega as categorias e atualiza os selects na tela atual
            const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
            await carregarCategorias(tipo, selectIds);
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Erro ao adicionar categoria:', error);
        showAlert('Erro de conexão.', 'error');
    }
}

function excluirCategoria(id) {
    document.getElementById('delete-categoria-id').value = id;
    openModal('modal-delete-categoria');
}

async function confirmarExclusaoCategoria() {
    const id = document.getElementById('delete-categoria-id').value;
    try {
        const response = await fetch('../backend/api/categorias/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        const data = await response.json();
        if (data.status === 'success') {
            closeModal('modal-delete-categoria');
            showAlert(data.message, 'success');
            const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';
            const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
            await carregarCategorias(tipo, selectIds);

            // Recarrega a lista para mostrar as transações atualizadas sem a categoria
            if (tipo === 'ganho' && typeof carregarGanhos === 'function') carregarGanhos();
            if (tipo === 'despesa' && typeof carregarDespesas === 'function') carregarDespesas();

        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Erro ao excluir categoria:', error);
        showAlert('Erro de conexão.', 'error');
    }
}

async function editarCategoriaNome(id, nomeAtual) {
    document.getElementById('edit-categoria-id').value = id;
    document.getElementById('edit-categoria-nome').value = nomeAtual;
    openModal('modal-edit-categoria');
}

async function salvarEdicaoCategoria() {
    const id = document.getElementById('edit-categoria-id').value;
    const novoNome = document.getElementById('edit-categoria-nome').value.trim();

    if (!novoNome) {
        showAlert('Digite o novo nome para a categoria.', 'error');
        return;
    }

    try {
        const response = await fetch('../backend/api/categorias/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nome: novoNome })
        });

        const data = await response.json();
        if (data.status === 'success') {
            closeModal('modal-edit-categoria');
            showAlert(data.message, 'success');
            
            const tipo = window.location.pathname.includes('ganhos') ? 'ganho' : 'despesa';
            const selectIds = tipo === 'ganho' ? ['ganho-categoria', 'edit-categoria'] : ['despesa-categoria', 'edit-categoria'];
            await carregarCategorias(tipo, selectIds);

            // Recarrega a lista para mostrar o novo nome nas transações
            if (tipo === 'ganho' && typeof carregarGanhos === 'function') carregarGanhos();
            if (tipo === 'despesa' && typeof carregarDespesas === 'function') carregarDespesas();

        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Erro ao editar categoria:', error);
        showAlert('Erro de conexão.', 'error');
    }
}
