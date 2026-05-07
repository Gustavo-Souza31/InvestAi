/**
 * api/utils/nav.js
 * Utilitários de navegação compartilhados entre páginas internas.
 * Destaca o link ativo na navbar com base na URL atual.
 */

document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname.toLowerCase();

    // Mapeamento de pathname → classe do link na navbar
    const navMap = {
        'dashboard.php':  '.nav-link-custom[href="dashboard.php"]',
        'resumo.php':     '.nav-resumo',
        'ganhos.php':     '.nav-ganhos',
        'despesas.php':   '.nav-despesas',
        'noticias.php':   '.nav-noticias',
        'perfil.php':     '.user-badge',
    };

    for (const [page, selector] of Object.entries(navMap)) {
        if (path.includes(page)) {
            const link = document.querySelector(selector);
            if (link && !link.classList.contains('active')) {
                link.classList.add('active');
            }
            break;
        }
    }
});
