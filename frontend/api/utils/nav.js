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

    // Menu hambúrguer (navbar mobile)
    const toggle = document.getElementById('navbarToggle');
    const links = document.getElementById('navbarLinks');
    if (!toggle || !links) return;

    const closeMenu = () => {
        links.classList.remove('open');
        toggle.classList.remove('active');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.querySelector('i').className = 'bi bi-list';
    };

    toggle.addEventListener('click', () => {
        const isOpen = links.classList.toggle('open');
        toggle.classList.toggle('active', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.querySelector('i').className = isOpen ? 'bi bi-x-lg' : 'bi bi-list';
    });

    links.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));

    document.addEventListener('click', (e) => {
        if (links.classList.contains('open') && !links.contains(e.target) && !toggle.contains(e.target)) {
            closeMenu();
        }
    });
});
