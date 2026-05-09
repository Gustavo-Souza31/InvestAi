(function () {
    const observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.08, rootMargin: '0px 0px -32px 0px' }
    );

    function observeAll() {
        document.querySelectorAll('.anim-on-scroll').forEach(function (el) {
            observer.observe(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', observeAll);
    } else {
        observeAll();
    }

    /* Re-observa elementos adicionados dinamicamente ao DOM */
    const mutationObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1) return;
                if (node.classList && node.classList.contains('anim-on-scroll')) {
                    observer.observe(node);
                }
                node.querySelectorAll && node.querySelectorAll('.anim-on-scroll').forEach(function (el) {
                    observer.observe(el);
                });
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        mutationObserver.observe(document.body, { childList: true, subtree: true });
    });
})();
