let categoriasAtuais = [];

async function carregarCategorias(tipo, selectIds) {
    try {
        const resposta = await fetch(`${BASE_PATH}/backend/api/categorias/read.php?tipo=${tipo}`);
        if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);
        const resultado = await resposta.json();

        if (resultado.status === 'success' && resultado.categorias) {
            categoriasAtuais = resultado.categorias;

            selectIds.forEach(id => {
                const select = document.getElementById(id);
                if (!select) return;
                const valueAntigo = select.value;
                select.innerHTML = '<option value="">Selecione uma categoria...</option>' +
                    categoriasAtuais.map(cat => {
                        const icon = CATEGORY_ICONS[cat.nome] || '📁';
                        return `<option value="${cat.id}">${icon} ${escapeHtml(cat.nome)}</option>`;
                    }).join('');
                if (valueAntigo) select.value = valueAntigo;
            });

            if (document.getElementById('modal-categorias')?.classList.contains('show')) {
                renderizarListaGerenciarCategorias();
            }
        }
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
    }
}
