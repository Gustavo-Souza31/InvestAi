(function () {
    function openLegalModal(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeLegalModal(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('show');
        }
        // Restore scroll only if no other legal modal is open
        const anyOpen = document.querySelector('.modal-legal-overlay.show');
        if (!anyOpen) document.body.style.overflow = '';
    }

    function init() {
        // Fechar ao clicar no overlay
        document.querySelectorAll('.modal-legal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeLegalModal(overlay.id);
            });
        });

        // Fechar com tecla ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-legal-overlay.show').forEach(function (el) {
                    closeLegalModal(el.id);
                });
            }
        });
    }

    window.openLegalModal = openLegalModal;
    window.closeLegalModal = closeLegalModal;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
