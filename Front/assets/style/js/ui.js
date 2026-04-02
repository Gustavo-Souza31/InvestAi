/**
 * ui.js — Comportamentos visuais e utilitários compartilhados
 *
 * Responsável por:
 * - Feedback visual (showAlert)
 * - Controle de modais (closeModal)
 * - Listener de fechamento de modal por clique no overlay
 * - Máscaras de input (CPF, telefone)
 */

// Exibe alerta de feedback na página.
// Suporta .alert-message (despesas/ganhos) e .auth-alert (login)
function showAlert(msg, type) {
    const el = document.querySelector('.alert-message') || document.querySelector('.auth-alert');
    if (!el) return;

    if (el.classList.contains('auth-alert')) {
        el.className   = `auth-alert ${type} show`;
        el.textContent = msg;
    } else {
        el.textContent   = msg;
        el.className     = 'alert-message ' + type;
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 4000);
    }
}

// Fecha um modal pelo ID
function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.classList.remove('show');
}

// Fecha modal ao clicar fora (no overlay)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('show');
        });
    });
});

// Máscara de CPF (000.000.000-00)
const cpfInput = document.getElementById('cadastro-cpf');
if (cpfInput) {
    cpfInput.addEventListener('input', () => {
        let v = cpfInput.value.replace(/\D/g, '');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        cpfInput.value = v;
    });
}

// Máscara de telefone ((00) 00000-0000)
const telInput = document.getElementById('cadastro-telefone');
if (telInput) {
    telInput.addEventListener('input', () => {
        let v = telInput.value.replace(/\D/g, '');
        v = v.replace(/^(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{5})(\d{4})$/, '$1-$2');
        telInput.value = v;
    });
}
