function renderizarSugestoes(sugestoes) {
    const container = document.getElementById('sugestoes-container');

    if (!container) {
        console.warn('Container #sugestoes-container não encontrado');
        return;
    }

    container.innerHTML = '';

    if (!sugestoes || sugestoes.length === 0) {
        container.innerHTML = `
            <div class="sugestoes-vazio">
                <i class="bi bi-check-circle"></i>
                <p>Parabéns! Seus gastos estão sob controle.</p>
            </div>
        `;
        return;
    }

    const header = document.createElement('div');
    header.className = 'sugestoes-header';
    const h2 = document.createElement('h2');
    h2.innerHTML = '<i class="bi bi-lightbulb-fill"></i>Sugestões de Economia';
    header.appendChild(h2);
    container.appendChild(header);

    const grid = document.createElement('div');
    grid.className = 'sugestoes-grid';

    sugestoes.forEach((sugestao) => {
        const card = renderizarSugestaoCard(sugestao);
        grid.appendChild(card);
    });

    container.appendChild(grid);
}

function renderizarSugestaoCard(sugestao) {
    const card = document.createElement('div');
    const tipo = sugestao.tipo || 'comportamento';

    const tipoConfig = {
        'quase_no_limite':     { icon: '🚨', label: 'Quase no Limite', badge: 'quase_no_limite' },
        'limite_atingido':     { icon: '😬', label: 'Limite Atingido',  badge: 'limite_atingido' },
        'limite_ultrapassado': { icon: '😰', label: 'Limite Excedido',  badge: 'limite_ultrapassado' },
        'orcamento':           { icon: '⚠️', label: 'Orçamento',        badge: 'orcamento' },
        'comportamento':       { icon: '📈', label: 'Gasto Elevado',    badge: 'comportamento' },
    };
    const cfg = tipoConfig[tipo] ?? tipoConfig['comportamento'];
    const isBadgeTipo = cfg.badge;
    const labelTipo   = cfg.label;
    const icon        = cfg.icon;

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

    // Header compacto
    const headerContainer = document.createElement('div');
    headerContainer.style.display = 'flex';
    headerContainer.style.alignItems = 'flex-start';
    headerContainer.style.justifyContent = 'space-between';
    headerContainer.style.marginBottom = '12px';
    headerContainer.style.gap = '8px';

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

    const mensagem = document.createElement('div');
    mensagem.className = 'sugestao-mensagem';
    mensagem.style.margin = '0 0 12px 0';
    mensagem.textContent = sugestao.mensagem || 'Considere reduzir seus gastos nesta categoria.';

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

    // Overlay de hover
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

    card.addEventListener('mouseenter', () => {
        overlay.style.display = 'flex';
        setTimeout(() => { overlay.style.opacity = '1'; }, 0);
    });

    card.addEventListener('mouseleave', () => {
        overlay.style.opacity = '0';
        setTimeout(() => { overlay.style.display = 'none'; }, 200);
    });

    overlay.onclick = (e) => {
        e.stopPropagation();
        regenerarSugestao(sugestao.id, card);
    };

    card.appendChild(headerContainer);
    card.appendChild(mensagem);
    card.appendChild(acoesLabel);
    card.appendChild(acoesList);
    card.appendChild(overlay);

    card.loadingIndicator = loadingIndicator;

    return card;
}
