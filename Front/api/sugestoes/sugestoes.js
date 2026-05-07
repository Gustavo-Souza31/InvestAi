/**
 * Front/api/sugestoes/sugestoes.js
 * 
 * Gerenciador de sugestões de economia
 * - Busca sugestões da API
 * - Renderiza cards de sugestões
 * - Integra com dashboard
 */

/**
 * Carregar sugestões de economia para o mês atual
 * @param {number} mes - Mês (1-12), padrão mês atual
 * @param {number} ano - Ano (YYYY), padrão ano atual
 * @returns {Promise<Array>} Array de sugestões
 */
async function carregarSugestoes(mes = null, ano = null) {
    try {
        // Usar mês/ano atual se não fornecido
        if (!mes || !ano) {
            const hoje = new Date();
            mes = mes || (hoje.getMonth() + 1);
            ano = ano || hoje.getFullYear();
        }

        const response = await fetch(
            `../backend/api/sugestoes/economia.php?mes=${mes}&ano=${ano}`,
            {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
            }
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

/**
 * Renderizar sugestões na página
 * @param {Array} sugestoes - Array de sugestões retornado da API
 */
function renderizarSugestoes(sugestoes) {
    const container = document.getElementById('sugestoes-container');

    if (!container) {
        console.warn('Container #sugestoes-container não encontrado');
        return;
    }

    // Limpar container
    container.innerHTML = '';

    // Se não há sugestões
    if (!sugestoes || sugestoes.length === 0) {
        container.innerHTML = `
            <div class="sugestoes-vazio">
                <i class="bi bi-check-circle"></i>
                <p>Parabéns! Seus gastos estão sob controle.</p>
            </div>
        `;
        return;
    }

    // Adicionar header
    const header = document.createElement('div');
    header.className = 'sugestoes-header';
    
    const h2 = document.createElement('h2');
    h2.innerHTML = '<i class="bi bi-lightbulb-fill"></i>Sugestões de Economia';
    
    header.appendChild(h2);
    container.appendChild(header);

    // Grid de cards
    const grid = document.createElement('div');
    grid.className = 'sugestoes-grid';

    sugestoes.forEach((sugestao) => {
        const card = renderizarSugestaoCard(sugestao);
        grid.appendChild(card);
    });

    container.appendChild(grid);
}

/**
 * Renderizar um único card de sugestão
 * @param {Object} sugestao - Dados da sugestão
 * @returns {HTMLElement} Card renderizado
 */
function renderizarSugestaoCard(sugestao) {
    const card = document.createElement('div');
    const tipo = sugestao.tipo || 'comportamento';
    const isBadgeTipo = tipo === 'orcamento' ? 'orcamento' : 'comportamento';
    const labelTipo = tipo === 'orcamento' ? 'Orçamento' : 'Gasto Elevado';
    const icon = tipo === 'orcamento' ? '⚠️' : '📈';

    card.className = `sugestao-card tipo-${tipo}`;
    card.style.display = 'flex';
    card.style.flexDirection = 'column';
    card.style.gap = '0';
    card.style.position = 'relative';
    card.style.cursor = 'pointer';
    card.style.transition = 'transform 0.2s ease, box-shadow 0.2s ease';

    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-4px)';
        card.style.boxShadow = '0 8px 24px rgba(168, 85, 247, 0.2)';
    });

    card.addEventListener('mouseleave', () => {
        if (!card.querySelector('[data-updating="true"]')) {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = 'none';
        }
    });

    card.onclick = async (e) => {
        if (card.querySelector('[data-updating="true"]')) return;
        e.stopPropagation();
        await regenerarSugestao(sugestao.id, card);
    };

    // ===== CONTAINER HEADER COMPACTO =====
    const headerContainer = document.createElement('div');
    headerContainer.style.display = 'flex';
    headerContainer.style.alignItems = 'flex-start';
    headerContainer.style.justifyContent = 'space-between';
    headerContainer.style.marginBottom = '12px';
    headerContainer.style.gap = '8px';

    // Header com ícone e conteúdo (esquerda)
    const headerCard = document.createElement('div');
    headerCard.style.display = 'flex';
    headerCard.style.alignItems = 'flex-start';
    headerCard.style.gap = '8px';
    headerCard.style.flex = '1';

    const iconEl = document.createElement('div');
    iconEl.style.fontSize = '1.2rem';
    iconEl.style.flexShrink = '0';
    iconEl.style.marginTop = '2px';
    iconEl.textContent = icon;

    const content = document.createElement('div');
    content.style.flex = '1';
    content.style.minWidth = '0';

    const titulo = document.createElement('div');
    titulo.className = 'sugestao-titulo';
    titulo.style.margin = '0';
    titulo.textContent = sugestao.titulo || 'Sugestão de Economia';

    const badge = document.createElement('span');
    badge.className = `sugestao-badge ${isBadgeTipo}`;
    badge.style.marginTop = '4px';
    badge.style.display = 'inline-block';
    badge.textContent = labelTipo;

    content.appendChild(titulo);
    content.appendChild(badge);
    headerCard.appendChild(iconEl);
    headerCard.appendChild(content);

    // Indicador de carregamento (escondido por padrão)
    const loadingIndicator = document.createElement('div');
    loadingIndicator.style.width = '28px';
    loadingIndicator.style.height = '28px';
    loadingIndicator.style.display = 'none';
    loadingIndicator.style.alignItems = 'center';
    loadingIndicator.style.justifyContent = 'center';
    loadingIndicator.style.color = '#a855f7';
    loadingIndicator.style.fontSize = '1rem';
    loadingIndicator.style.flexShrink = '0';
    loadingIndicator.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    loadingIndicator.setAttribute('data-updating', 'false');

    headerContainer.appendChild(headerCard);
    headerContainer.appendChild(loadingIndicator);

    // Mensagem
    const mensagem = document.createElement('div');
    mensagem.className = 'sugestao-mensagem';
    mensagem.style.margin = '0 0 12px 0';
    mensagem.textContent = sugestao.mensagem || 'Considere reduzir seus gastos nesta categoria.';

    // Ações
    const acoesLabel = document.createElement('span');
    acoesLabel.className = 'sugestao-acoes-label';
    acoesLabel.style.margin = '0 0 8px 0';
    acoesLabel.textContent = 'Como economizar:';

    const acoesList = document.createElement('ul');
    acoesList.className = 'sugestao-acoes-list';
    acoesList.style.margin = '0';

    if (sugestao.acoes && Array.isArray(sugestao.acoes)) {
        sugestao.acoes.forEach((acao) => {
            const li = document.createElement('li');
            li.className = 'sugestao-acao-item';
            li.textContent = acao;
            acoesList.appendChild(li);
        });
    }

    // ===== OVERLAY DE HOVER =====
    const overlay = document.createElement('div');
    overlay.style.position = 'absolute';
    overlay.style.inset = '0';
    overlay.style.background = 'rgba(20, 20, 30, 0.8)';
    overlay.style.borderRadius = 'inherit';
    overlay.style.display = 'none';
    overlay.style.flexDirection = 'column';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.opacity = '0';
    overlay.style.transition = 'opacity 0.2s ease';
    overlay.style.zIndex = '10';
    overlay.style.cursor = 'pointer';

    const overlayText = document.createElement('div');
    overlayText.style.fontSize = '0.95rem';
    overlayText.style.color = '#e2e8f0';
    overlayText.style.fontWeight = '500';
    overlayText.textContent = 'Atualizar sugestão?';

    overlay.appendChild(overlayText);

    // Event listeners para mostrar/esconder overlay
    card.addEventListener('mouseenter', () => {
        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.style.opacity = '1';
        }, 0);
    });

    card.addEventListener('mouseleave', () => {
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 200);
    });

    overlay.onclick = (e) => {
        e.stopPropagation();
        regenerarSugestao(sugestao.id, card);
    };

    // Montar card
    card.appendChild(headerContainer);
    card.appendChild(mensagem);
    card.appendChild(acoesLabel);
    card.appendChild(acoesList);
    card.appendChild(overlay);
    
    // Guardar referência ao loading indicator no card
    card.loadingIndicator = loadingIndicator;

    return card;
}

/**
 * Inicializar sugestões na dashboard
 * Chamado por dashboard.js após carregar os dados principais
 * 
 * @param {number} mes - Mês (1-12)
 * @param {number} ano - Ano (YYYY)
 */
async function inicializarSugestoes(mes = null, ano = null) {
    const container = document.getElementById('sugestoes-container');

    if (!container) {
        console.warn('Container #sugestoes-container não encontrado. Pulando sugestões.');
        return;
    }

    // Mostrar loading
    container.innerHTML = `
        <div class="sugestoes-header">
            <h2><i class="bi bi-lightbulb-fill"></i>Sugestões de Economia</h2>
        </div>
        <div class="sugestoes-loading">
            Carregando sugestões
        </div>
    `;

    // Carregar sugestões
    const sugestoes = await carregarSugestoes(mes, ano);

    // Renderizar
    renderizarSugestoes(sugestoes);
}

/**
 * Regenerar sugestão com a IA
 */
async function regenerarSugestao(sugestaoId, cardElement) {
    if (!cardElement || !cardElement.loadingIndicator) return;
    
    const loadingIndicator = cardElement.loadingIndicator;
    loadingIndicator.style.display = 'flex';
    loadingIndicator.setAttribute('data-updating', 'true');
    cardElement.style.pointerEvents = 'none';
    cardElement.style.opacity = '0.7';
    
    try {
        const response = await fetch(
            `../backend/api/sugestoes/regenerar.php`,
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sugestao_id: sugestaoId })
            }
        );

        if (response.ok) {
            const hoje = new Date();
            const mes = hoje.getMonth() + 1;
            const ano = hoje.getFullYear();
            const sugestoes = await carregarSugestoes(mes, ano);
            renderizarSugestoes(sugestoes);
        } else {
            loadingIndicator.style.display = 'none';
            loadingIndicator.setAttribute('data-updating', 'false');
            cardElement.style.pointerEvents = 'auto';
            cardElement.style.opacity = '1';
        }
    } catch (error) {
        console.error('Erro ao regenerar:', error);
        loadingIndicator.style.display = 'none';
        loadingIndicator.setAttribute('data-updating', 'false');
        cardElement.style.pointerEvents = 'auto';
        cardElement.style.opacity = '1';
    }
}

// Exportar funções para uso global
window.sugestoesAPI = {
    carregar: carregarSugestoes,
    renderizar: renderizarSugestoes,
    inicializar: inicializarSugestoes,
};
