async function carregarSugestoes(mes = null, ano = null) {
    try {
        if (!mes || !ano) {
            const hoje = new Date();
            mes = mes || (hoje.getMonth() + 1);
            ano = ano || hoje.getFullYear();
        }

        const response = await fetch(
            `${BASE_PATH}/backend/api/sugestoes/economia.php?mes=${mes}&ano=${ano}`,
            { method: 'GET', headers: { 'Content-Type': 'application/json' } }
        );

        if (!response.ok) {
            console.error(`Erro ao carregar sugestões: ${response.status}`);
            return [];
        }

        const data = await response.json();

        if (data.status === 'success') {
            return data.sugestoes || [];
        } else {
            console.error('Erro na resposta de sugestões:', data.mensagem);
            return [];
        }
    } catch (error) {
        console.error('Erro ao buscar sugestões:', error);
        return [];
    }
}

async function inicializarSugestoes(mes = null, ano = null) {
    const container = document.getElementById('sugestoes-container');

    if (!container) {
        console.warn('Container #sugestoes-container não encontrado. Pulando sugestões.');
        return;
    }

    container.innerHTML = `
        <div class="sugestoes-header">
            <h2><i class="bi bi-lightbulb-fill"></i>Sugestões de Economia</h2>
        </div>
        <div class="sugestoes-loading">
            Carregando sugestões
        </div>
    `;

    const sugestoes = await carregarSugestoes(mes, ano);
    renderizarSugestoes(sugestoes);
}

window.sugestoesAPI = {
    carregar: carregarSugestoes,
    renderizar: renderizarSugestoes,
    inicializar: inicializarSugestoes,
};
