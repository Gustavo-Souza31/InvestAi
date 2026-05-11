async function regenerarSugestao(sugestaoId, cardElement) {
    if (!cardElement || !cardElement.loadingIndicator) return;

    const loadingIndicator = cardElement.loadingIndicator;
    loadingIndicator.style.display = 'flex';
    loadingIndicator.setAttribute('data-updating', 'true');
    cardElement.style.pointerEvents = 'none';
    cardElement.style.opacity = '0.7';

    try {
        const response = await fetch(
            `${BASE_PATH}/backend/api/sugestoes/regenerar.php`,
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
