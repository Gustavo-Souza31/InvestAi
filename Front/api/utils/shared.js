/**
 * shared.js — Funções auxiliares compartilhadas por TODOS os arquivos render.js
 *
 * Funções implementadas:
 * - formatMoney(val): Formata número em moeda brasileira
 * - formatDate(dateStr): Converte YYYY-MM-DD para DD/MM/YYYY
 * - escapeHtml(text): Escapa caracteres HTML para prevenir XSS
 * - openEdit(id, desc, valor, data, fixo): Abre modal de edição
 * - openDelete(id): Abre modal de confirmação de deleção
 */

/**
 * Formata valor numérico em moeda brasileira (R$)
 * @param {number} val - Valor a formatar
 * @returns {string} Valor formatado como moeda
 */
function formatMoney(val) {
    return parseFloat(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

/**
 * Formata data de YYYY-MM-DD para DD/MM/YYYY
 * @param {string} dateStr - Data no formato YYYY-MM-DD
 * @returns {string} Data formatada como DD/MM/YYYY
 */
function formatDate(dateStr) {
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

/**
 * Escapa caracteres HTML para prevenir ataques XSS
 * @param {string} text - Texto a escapar
 * @returns {string} Texto com HTML escapado
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Abre modal de edição preenchendo os campos com dados atuais
 * @param {number} id - ID do registro
 * @param {string} descricao - Descrição do registro
 * @param {number} valor - Valor do registro
 * @param {string} data - Data do registro (YYYY-MM-DD)
 * @param {number} fixo - 0 ou 1 indicando se é fixo
 */
function openEdit(id, descricao, valor, data, fixo) {
    document.getElementById('edit-id').value        = id;
    document.getElementById('edit-descricao').value = descricao;
    document.getElementById('edit-valor').value     = valor;
    document.getElementById('edit-data').value      = data;
    document.getElementById('edit-fixo').checked    = parseInt(fixo) === 1;
    document.getElementById('modal-edit').classList.add('show');
}

/**
 * Abre modal de confirmação para exclusão
 * @param {number} id - ID do registro a excluir
 */
function openDelete(id) {
    document.getElementById('delete-id').value = id;
    document.getElementById('modal-delete').classList.add('show');
}
