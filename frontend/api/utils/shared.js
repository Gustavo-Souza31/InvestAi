// ===== BASE PATH DINÂMICO =====
// Detecta automaticamente o caminho até a pasta "inventai" na URL.
// Funciona independente de onde o projeto estiver (ex: /gustavo/inventai, /joao/inventai)
const BASE_PATH = (() => {
    const path = window.location.pathname;
    const idx = path.indexOf('/inventai');
    if (idx !== -1) {
        return path.substring(0, idx + '/inventai'.length);
    }
    // Fallback: tenta encontrar na URL completa
    return '/inventai';
})();

// Formata número para padrão de dinheiro brasileiro (R$ 1.234,56)
function formatMoney(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// Formata data de YYYY-MM-DD para DD/MM/YYYY
function formatDate(dataString) {
    const [ano, mes, dia] = dataString.split('-');
    return `${dia}/${mes}/${ano}`;
}

// Escapa caracteres HTML especiais para prevenir XSS (segurança)
function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

// Abre modal de edição e preenche os campos com valores atuais
function openEdit(id, descricao, valor, data, fixo, categoriaId = null) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-descricao').value = descricao;
    document.getElementById('edit-valor').value = valor;
    document.getElementById('edit-data').value = data;
    document.getElementById('edit-fixo').checked = parseInt(fixo) === 1;
    if (document.getElementById('edit-categoria')) {
        document.getElementById('edit-categoria').value = categoriaId || '';
    }
    document.getElementById('modal-edit').classList.add('show');
}

// Abre modal de confirmação para deletar item
function openDelete(id) {
    document.getElementById('delete-id').value = id;
    document.getElementById('modal-delete').classList.add('show');
}
