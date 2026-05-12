async function listarOrcamentos() {
    const resposta = await fetch(BASE_PATH + '/backend/api/orcamento/read.php');
    return await resposta.json();
}

async function carregarCategoriasNoModal() {
    try {
        const resposta = await fetch(BASE_PATH + '/backend/api/categorias/read.php?tipo=despesa');
        const resultado = await resposta.json();

        if (resultado.status === 'success' && resultado.categorias) {
            const select = document.getElementById('orc-categoria');

            const valorAnterior = select.value;

            select.innerHTML = '<option value="">Selecione uma categoria...</option>';

            resultado.categorias.forEach(cat => {
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
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
    }
}
