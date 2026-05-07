async function listarOrcamentos() {
    const resposta = await fetch('/inventai/backend/api/orcamento/read.php');
    return await resposta.json();
}

// ─── Carregar categorias de despesa e popular o select ──────────────────────
async function carregarCategoriasNoModal() {
    try {
        const res = await fetch('/inventai/backend/api/categorias/read.php?tipo=despesa');
        const data = await res.json();

        if (data.status === 'success' && data.categorias) {
            const select = document.getElementById('orc-categoria');

            const valorAnterior = select.value;

            select.innerHTML = '<option value="">Selecione uma categoria...</option>';

            data.categorias.forEach(cat => {
                const icon = ORC_ICONS[cat.nome] || '📁';
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = `${icon} ${cat.nome}`;
                select.appendChild(option);
            });

            if (valorAnterior) {
                select.value = valorAnterior;
            }
        }
    } catch (e) {
        console.error('Erro ao carregar categorias:', e);
    }
}
