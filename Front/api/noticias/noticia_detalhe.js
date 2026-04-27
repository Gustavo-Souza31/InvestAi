/**
 * Front/api/noticias/noticia_detalhe.js
 * Carrega a notícia do localStorage e busca a explicação didática da IA.
 */

/* ─── Carrega dados da notícia ─────────────────────────────────────────────── */
const noticia = JSON.parse(localStorage.getItem('investai_noticia_detalhe') || 'null');

const tituloEl          = document.getElementById('detalhe-titulo');
const metaEl            = document.getElementById('detalhe-meta');
const resumoOriginalEl  = document.getElementById('detalhe-resumo-original');
const btnOriginalEl     = document.getElementById('btn-noticia-original');
const iaLoadingEl       = document.getElementById('ia-loading');
const iaConteudoEl      = document.getElementById('ia-conteudo');

/* ─── Helpers ──────────────────────────────────────────────────────────────── */
function impactoClass(nivel) {
    const map = { alto: 'impacto-alto', medio: 'impacto-medio', baixo: 'impacto-baixo' };
    return map[nivel] || 'impacto-baixo';
}

function impactoLabel(nivel) {
    const map = { alto: '⚠ Impacto Alto', medio: '● Impacto Médio', baixo: '✓ Impacto Baixo' };
    return map[nivel] || nivel;
}

/* ─── Preenche o header com dados da notícia ───────────────────────────────── */
function preencherHeader(n) {
    document.title = `InvestAi — ${n.titulo.slice(0, 60)}`;

    let fonteClass = '';
    if (n.fonte.includes('G1')) fonteClass = 'fonte-g1';
    else if (n.fonte.includes('InfoMoney')) fonteClass = 'fonte-infomoney';
    else if (n.fonte.includes('Valor')) fonteClass = 'fonte-valor';

    metaEl.innerHTML = `
        <span class="fonte-tag ${fonteClass}">
            <i class="bi ${n.icone_fonte}"></i>${n.fonte}
        </span>
        <span class="detalhe-data"><i class="bi bi-clock me-1"></i>${n.data}</span>`;

    tituloEl.textContent = n.titulo;
    resumoOriginalEl.textContent = n.resumo;

    if (n.url && n.url !== '#') {
        btnOriginalEl.href = n.url;
        btnOriginalEl.style.display = 'inline-flex';
    } else {
        btnOriginalEl.style.display = 'none';
    }
}

/* ─── Renderiza a explicação da IA ─────────────────────────────────────────── */
function renderExplicacao(data) {
    // Função auxiliar para extrair texto de objetos da IA
    const extrairTexto = (item) => {
        if (typeof item === 'string') return item;
        if (typeof item === 'object' && item !== null) {
            // Tenta pegar o valor de qualquer chave (Indicador, Ação, Termo, etc)
            const valores = Object.values(item);
            return valores.length > 0 ? valores.join(': ') : JSON.stringify(item);
        }
        return String(item);
    };

    // Preparar lista de ações (Plano de Ação)
    const acoesHTML = (data.plano_de_acao || []).map(a =>
        `<li><i class="bi bi-check2-circle"></i><span>${extrairTexto(a)}</span></li>`
    ).join('');

    // Preparar glossário
    const glossarioHTML = (data.glossario_tecnico || []).map(g => `
        <div class="glossario-item">
            <div class="glossario-termo">${extrairTexto(g.termo || g)}</div>
            <div class="glossario-def">${extrairTexto(g.definicao || g)}</div>
        </div>`
    ).join('');

    // Preparar indicadores afetados (Badges Minimalistas)
    const indicadoresHTML = (data.indicadores_afetados || []).map(i => {
        const texto = extrairTexto(i);
        const isAlta = texto.toLowerCase().includes('alta') || texto.toLowerCase().includes('subir');
        const color = isAlta ? 'var(--color-expense)' : 'var(--color-gain)';
        const icon = isAlta ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
        
        return `<span class="badge-tendencia" style="border-color: rgba(255,255,255,0.1); color: ${color}; background: rgba(255,255,255,0.03);">
            <i class="bi ${icon} me-1"></i>${texto.replace(':', ' + ')}
        </span>`;
    }).join('');

    iaConteudoEl.innerHTML = `

        <!-- Manchete Premium -->
        <div class="explicacao-tweet">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>${data.manchete || ''}</span>
        </div>

        <!-- Nível de impacto e Indicadores -->
        <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
            <div class="impacto-badge ${impactoClass(data.nivel_impacto?.toLowerCase())}">
                ${impactoLabel(data.nivel_impacto?.toLowerCase())}
            </div>
            ${indicadoresHTML}
        </div>

        <!-- Grid principal -->
        <div class="explicacao-grid">

            <!-- Resumo Executivo -->
            <div class="explicacao-card card-aconteceu">
                <div class="explicacao-card-label">
                    <i class="bi bi-journal-text"></i> Resumo Executivo
                </div>
                <p>${data.resumo_executivo || ''}</p>
            </div>

            <!-- Análise de Cenário -->
            <div class="explicacao-card card-importa">
                <div class="explicacao-card-label">
                    <i class="bi bi-eye"></i> Análise de Cenário
                </div>
                <p>${data.analise_de_cenario || ''}</p>
            </div>

            <!-- Impacto no Bolso e Metas -->
            <div class="explicacao-card card-bolso">
                <div class="explicacao-card-label">
                    <i class="bi bi-bullseye"></i> Impacto no Bolso e Metas
                </div>
                <p>${data.impacto_bolso_e_metas || ''}</p>
            </div>

            <!-- Plano de Ação Tático -->
            ${acoesHTML ? `
            <div class="explicacao-card card-acoes">
                <div class="explicacao-card-label">
                    <i class="bi bi-shield-check"></i> Plano de Ação Tático
                </div>
                <ul class="acoes-list">${acoesHTML}</ul>
            </div>` : ''}

        </div>

        <!-- Glossário Técnico -->
        ${glossarioHTML ? `
        <div class="glossario-section">
            <div class="glossario-titulo">
                <i class="bi bi-book"></i> Glossário Técnico
            </div>
            <div class="glossario-grid">${glossarioHTML}</div>
        </div>` : ''}

        <!-- Botão notícia original (rodapé) -->
        ${noticia && noticia.url && noticia.url !== '#' ? `
        <div class="detalhe-footer-btn">
            <a href="${noticia.url}" target="_blank" rel="noopener" class="btn-original large">
                <i class="bi bi-box-arrow-up-right"></i>
                Ler a notícia completa no ${noticia.fonte}
            </a>
        </div>` : ''}
    `;

    iaConteudoEl.style.display = 'block';
}

/* ─── Busca explicação na IA ────────────────────────────────────────────────── */
async function buscarExplicacao(n) {
    try {
        const resp = await fetch('../backend/api/noticias/explain.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ noticia: n })
        });
        const data = await resp.json();

        iaLoadingEl.style.display = 'none';

        if (data.status === 'ok') {
            renderExplicacao(data);
        } else {
            let msgErro = data.mensagem || 'Erro desconhecido.';
            let tituloErro = 'Não foi possível gerar a explicação';
            
            // Tratativa amigável para erro de cota da API Gemini (429)
            if (msgErro.includes('429') || msgErro.includes('Quota') || msgErro.includes('exceeded')) {
                tituloErro = 'Limite de análises da IA atingido';
                msgErro = 'A cota gratuita da nossa inteligência artificial esgotou por enquanto. Por favor, tente novamente em alguns minutos.';
            }

            iaConteudoEl.innerHTML = `
                <div class="detalhe-error">
                    <i class="bi bi-exclamation-triangle-fill" style="color: #fb923c;"></i>
                    <h3>${tituloErro}</h3>
                    <p>${msgErro}</p>
                </div>`;
            iaConteudoEl.style.display = 'block';
        }
    } catch (err) {
        iaLoadingEl.style.display = 'none';
        
        let msgErro = err.message;
        let tituloErro = 'Erro de conexão';
        
        if (msgErro.includes('429') || msgErro.includes('Quota') || msgErro.includes('exceeded')) {
            tituloErro = 'Limite de análises da IA atingido';
            msgErro = 'A cota gratuita da nossa inteligência artificial esgotou por enquanto. Por favor, tente novamente em alguns minutos.';
        }

        iaConteudoEl.innerHTML = `
            <div class="detalhe-error">
                <i class="bi bi-wifi-off" style="color: #ef4444;"></i>
                <h3>${tituloErro}</h3>
                <p>${msgErro}</p>
            </div>`;
        iaConteudoEl.style.display = 'block';
    }
}

/* ─── Init ──────────────────────────────────────────────────────────────────── */
if (!noticia) {
    tituloEl.textContent = 'Notícia não encontrada.';
    iaLoadingEl.style.display = 'none';
    iaConteudoEl.innerHTML = `
        <div class="detalhe-error">
            <i class="bi bi-newspaper"></i>
            <h3>Nenhuma notícia selecionada</h3>
            <p>Volte para a lista de notícias e clique em uma delas.</p>
            <a href="noticias.php" class="btn-original" style="margin-top:16px;">
                <i class="bi bi-arrow-left"></i> Ir para Notícias & IA
            </a>
        </div>`;
    iaConteudoEl.style.display = 'block';
} else {
    preencherHeader(noticia);
    buscarExplicacao(noticia);
}
