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
    const acoesHTML = (data.o_que_fazer || []).map(a =>
        `<li><i class="bi bi-check2-circle"></i><span>${a}</span></li>`
    ).join('');

    const palavrasHTML = (data.palavras_chave || []).map(p => `
        <div class="glossario-item">
            <div class="glossario-termo">${p.termo}</div>
            <div class="glossario-def">${p.definicao}</div>
        </div>`
    ).join('');

    iaConteudoEl.innerHTML = `

        <!-- Tweet / Manchete simplificada -->
        <div class="explicacao-tweet">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>${data.resumo_tweet || data.manchete || ''}</span>
        </div>

        <!-- Nível de impacto -->
        <div class="impacto-badge ${impactoClass(data.nivel_impacto)}">
            ${impactoLabel(data.nivel_impacto)}
        </div>

        <!-- Grid principal -->
        <div class="explicacao-grid">

            <!-- O que aconteceu -->
            <div class="explicacao-card card-aconteceu">
                <div class="explicacao-card-label">
                    <i class="bi bi-newspaper"></i> O que aconteceu
                </div>
                <p>${data.o_que_aconteceu || ''}</p>
            </div>

            <!-- Por que importa -->
            <div class="explicacao-card card-importa">
                <div class="explicacao-card-label">
                    <i class="bi bi-exclamation-circle"></i> Por que importa
                </div>
                <p>${data.por_que_importa || ''}</p>
            </div>

            <!-- Impacto no bolso -->
            <div class="explicacao-card card-bolso">
                <div class="explicacao-card-label">
                    <i class="bi bi-wallet2"></i> Impacto no seu bolso
                </div>
                <p>${data.impacto_no_bolso || ''}</p>
            </div>

            <!-- O que fazer -->
            ${acoesHTML ? `
            <div class="explicacao-card card-acoes">
                <div class="explicacao-card-label">
                    <i class="bi bi-check2-all"></i> O que você pode fazer
                </div>
                <ul class="acoes-list">${acoesHTML}</ul>
            </div>` : ''}

        </div>

        <!-- Glossário -->
        ${palavrasHTML ? `
        <div class="glossario-section">
            <div class="glossario-titulo">
                <i class="bi bi-book"></i> Entenda os termos
            </div>
            <div class="glossario-grid">${palavrasHTML}</div>
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
