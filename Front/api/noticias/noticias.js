/**
 * Front/api/noticias/noticias.js — v4
 * Dados já vêm injetados pelo PHP em window.INVESTAI_DATA (sem fetch/sessão).
 * Fluxo:
 *  1. Renderiza cards imediatamente com os dados do PHP
 *  2. "Atualizar" → executa cron via CLI e recarrega a página
 *  3. "Analisar com IA" → análise personalizada on-demand
 */

/* ─── Estado global ────────────────────────────────────────────────────────── */
const DATA            = window.INVESTAI_DATA || {};
let todasNoticias     = DATA.noticias          || [];
let filtroFonte       = 'todas';
let filtroCategoria   = 'todas';
const contagemCat     = DATA.contagemCat       || {};
const totalRelevantes = DATA.totalRelevantes   || 0;

/* ─── Refs DOM ─────────────────────────────────────────────────────────────── */
const gridEl      = document.getElementById('noticias-grid');
const countEl     = document.getElementById('noticias-count');
const btnAnalisar = document.getElementById('btn-analisar');
const btnAtualizar= document.getElementById('btn-atualizar');
const iaPanelEl   = document.getElementById('ia-panel');
const iaLoadingEl = document.getElementById('ia-loading');
const iaConteudoEl= document.getElementById('ia-conteudo');
const catFiltersEl= document.getElementById('categoria-filters');
const toastEl     = document.getElementById('news-toast');

/* ─── Mapa de categorias ───────────────────────────────────────────────────── */
const CATEGORIA_MAP = {
    'Transporte':      { cls: 'cat-transporte',  icone: 'bi-truck',          label: 'Transporte'      },
    'Alimentação':     { cls: 'cat-alimentacao', icone: 'bi-basket2',         label: 'Alimentação'     },
    'Moradia':         { cls: 'cat-moradia',     icone: 'bi-house',           label: 'Moradia'         },
    'Lazer':           { cls: 'cat-lazer',       icone: 'bi-controller',      label: 'Lazer'           },
    'Tecnologia':      { cls: 'cat-tecnologia',  icone: 'bi-cpu',             label: 'Tecnologia'      },
    'Saúde':           { cls: 'cat-saude',       icone: 'bi-heart-pulse',     label: 'Saúde'           },
    'Finanças Gerais': { cls: 'cat-financas',    icone: 'bi-graph-up-arrow',  label: 'Finanças Gerais' },
};

/* ─── Helpers ──────────────────────────────────────────────────────────────── */
function badgeCategoria(categoria) {
    if (!categoria) return '';
    const m = CATEGORIA_MAP[categoria] || CATEGORIA_MAP['Finanças Gerais'];
    return `<span class="badge-categoria ${m.cls}"><i class="bi ${m.icone}"></i>${m.label}</span>`;
}

function badgeImpacto(nivel) {
    const map = {
        alto:  { cls: 'alto',  icone: 'bi-exclamation-triangle-fill', label: 'Impacto Alto'  },
        medio: { cls: 'medio', icone: 'bi-dash-circle-fill',          label: 'Impacto Médio' },
        baixo: { cls: 'baixo', icone: 'bi-check-circle-fill',         label: 'Impacto Baixo' },
    };
    const m = map[nivel] || map.baixo;
    return `<span class="badge-impacto ${m.cls}"><i class="bi ${m.icone}"></i>${m.label}</span>`;
}

function showToast(msg, tipo = 'info') {
    if (!toastEl) return;
    const iconMap = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
    toastEl.className = `news-toast ${tipo} show`;
    toastEl.querySelector('.toast-msg').textContent = msg;
    toastEl.querySelector('i').className = `bi ${iconMap[tipo] || iconMap.info}`;
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => toastEl.classList.remove('show'), 4000);
}

/* ─── Render cards ─────────────────────────────────────────────────────────── */
function renderNoticias(lista) {
    if (!lista || lista.length === 0) {
        gridEl.innerHTML = `
            <div class="empty-state" style="grid-column:1/-1">
                <i class="bi bi-newspaper"></i>
                <p>Nenhuma notícia encontrada.<br>
                   <small>Clique em <strong>Atualizar</strong> para buscar novas notícias.</small>
                </p>
            </div>`;
        countEl.textContent = '0 notícias';
        return;
    }

    countEl.textContent = `${lista.length} notícia${lista.length !== 1 ? 's' : ''}`;

    // Agrupar notícias por categoria
    const grouped = {};
    lista.forEach(n => {
        const cat = n.categoria || 'Finanças Gerais';
        if (!grouped[cat]) grouped[cat] = [];
        grouped[cat].push(n);
    });

    const categoriasOrdenadas = ['Transporte', 'Alimentação', 'Moradia', 'Lazer', 'Tecnologia', 'Saúde', 'Finanças Gerais'];
    let html = '';

    categoriasOrdenadas.forEach(cat => {
        if (!grouped[cat] || grouped[cat].length === 0) return;

        const m = CATEGORIA_MAP[cat] || CATEGORIA_MAP['Finanças Gerais'];

        // Cabeçalho da seção da categoria
        html += `
        <div class="categoria-section-header" style="grid-column: 1 / -1; margin-top: 15px; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 8px;">
            <i class="bi ${m.icone}" style="font-size: 1.2rem; color: var(--text-muted);"></i>
            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 600; color: #fff;">${m.label}</h3>
        </div>`;

        // Cards da categoria
        html += grouped[cat].map(n => {
            const destaqueClass = n.impacto_pessoal ? 'destaque' : '';
            const badgePessoal  = n.impacto_pessoal
                ? `<div class="badge-destaque-usuario"><i class="bi bi-bell-fill"></i>Afeta suas despesas</div>` : '';
            const btnOriginal   = n.url && n.url !== '#'
                ? `<a href="${n.url}" target="_blank" rel="noopener" class="btn-card-original" onclick="event.stopPropagation()">
                       <i class="bi bi-box-arrow-up-right"></i>Original
                   </a>`
                : `<span class="link-btn"><i class="bi bi-stars me-1"></i>Entender com IA</span>`;
            
            // Gerar um ID seguro para associar o clique ao objeto
            const safeId = "noticia_" + n.id;
            window[safeId] = n; // Armazena a referência global temporariamente para o evento de clique

            // Definir classe da fonte baseada no nome
            let fonteClass = '';
            if (n.fonte.includes('G1')) fonteClass = 'fonte-g1';
            else if (n.fonte.includes('InfoMoney')) fonteClass = 'fonte-infomoney';
            else if (n.fonte.includes('Valor')) fonteClass = 'fonte-valor';

            return `
            <div class="noticia-card ${destaqueClass}"
                 data-noticia-id="${safeId}" data-fonte="${n.fonte}" data-categoria="${n.categoria || ''}">
                <div class="card-header-row">
                    <span class="fonte-tag ${fonteClass}">
                        <i class="bi ${n.icone_fonte}"></i>${n.fonte}
                    </span>
                    ${badgeImpacto(n.nivel_impacto)}
                </div>
                <div class="titulo">${n.titulo}</div>
                <div class="resumo">${n.resumo}</div>
                ${badgePessoal}
                <div class="card-footer-row">
                    <span class="data"><i class="bi bi-clock me-1"></i>${n.data}</span>
                    ${btnOriginal}
                </div>
            </div>`;
        }).join('');
    });

    gridEl.innerHTML = html;

    gridEl.querySelectorAll('.noticia-card').forEach(card => {
        card.addEventListener('click', e => {
            if (e.target.closest('.btn-card-original')) return;
            const nId = card.dataset.noticiaId;
            openDetalhe(window[nId]);
        });
    });
}

function openDetalhe(noticia) {
    localStorage.setItem('investai_noticia_detalhe', JSON.stringify(noticia));
    window.open('noticia_detalhe.php', '_blank');
}
window.openDetalhe = openDetalhe;

/* ─── Filtros combinados ───────────────────────────────────────────────────── */
function aplicarFiltros() {
    let lista = todasNoticias;
    if (filtroFonte !== 'todas') lista = lista.filter(n => n.fonte === filtroFonte);
    if (filtroCategoria === 'relevante') lista = lista.filter(n => n.impacto_pessoal);
    else if (filtroCategoria !== 'todas') lista = lista.filter(n => n.categoria === filtroCategoria);
    renderNoticias(lista);
}

/* ─── Filtros de fonte ─────────────────────────────────────────────────────── */
document.querySelectorAll('.fonte-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        filtroFonte = btn.dataset.fonte;
        document.querySelectorAll('.fonte-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.fonte === filtroFonte)
        );
        aplicarFiltros();
    });
});

/* ─── Barra de categorias (sempre visível com contagem do banco) ───────────── */
function renderFiltrosCategorias() {
    const CATS = ['Transporte','Alimentação','Moradia','Lazer','Tecnologia','Saúde','Finanças Gerais'];
    let html = `<button class="cat-filter-btn active" data-cat="todas">Todas</button>`;

    if (totalRelevantes > 0) {
        html += `<button class="cat-filter-btn relevante" data-cat="relevante">
            <i class="bi bi-bell-fill"></i>Afeta você <span class="cat-count">${totalRelevantes}</span>
        </button>`;
    }

    CATS.forEach(cat => {
        const m   = CATEGORIA_MAP[cat];
        if (!m) return;
        const qtd = contagemCat[cat] || 0;
        html += `<button class="cat-filter-btn ${qtd === 0 ? 'vazia' : ''}" data-cat="${cat}" ${qtd === 0 ? 'disabled' : ''}>
            <i class="bi ${m.icone}"></i>${m.label}
            ${qtd > 0 ? `<span class="cat-count">${qtd}</span>` : ''}
        </button>`;
    });

    catFiltersEl.innerHTML = html;

    catFiltersEl.querySelectorAll('.cat-filter-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', () => {
            filtroCategoria = btn.dataset.cat;
            catFiltersEl.querySelectorAll('.cat-filter-btn').forEach(b =>
                b.classList.toggle('active', b.dataset.cat === filtroCategoria)
            );
            aplicarFiltros();
        });
    });
}

/* ─── Init: renderizar imediatamente ───────────────────────────────────────── */
renderFiltrosCategorias();
aplicarFiltros();

/* ─── Botão Atualizar ──────────────────────────────────────────────────────── */
btnAtualizar.addEventListener('click', async () => {
    btnAtualizar.disabled = true;
    btnAtualizar.innerHTML = '<i class="bi bi-hourglass-split"></i>Atualizando...';
    showToast('Iniciando busca de novas notícias...', 'info');

    try {
        const resp = await fetch('../backend/run_cron.php', { credentials: 'include' });
        const data = await resp.json();

        if (data.status === 'iniciado') {
            showToast('⏳ Processando em background (~25s)...', 'info');
            setTimeout(() => {
                showToast('✅ Recarregando página com novas notícias!', 'success');
                setTimeout(() => location.reload(), 1200);
            }, 25000);
        } else if (data.status === 'skipped') {
            showToast('⏱ ' + data.mensagem, 'info');
            btnAtualizar.disabled = false;
            btnAtualizar.innerHTML = '<i class="bi bi-arrow-repeat"></i>Atualizar';
        } else {
            showToast('Erro: ' + (data.mensagem || 'falha'), 'error');
            btnAtualizar.disabled = false;
            btnAtualizar.innerHTML = '<i class="bi bi-arrow-repeat"></i>Atualizar';
        }
    } catch (err) {
        showToast('Erro: ' + err.message, 'error');
        btnAtualizar.disabled = false;
        btnAtualizar.innerHTML = '<i class="bi bi-arrow-repeat"></i>Atualizar';
    }
});

/* ─── Botão Analisar com IA ────────────────────────────────────────────────── */
btnAnalisar.addEventListener('click', async () => {
    if (!todasNoticias.length) return;
    btnAnalisar.disabled = true;
    btnAnalisar.innerHTML = '<i class="bi bi-hourglass-split"></i>Analisando...';
    iaPanelEl.style.display = 'block';
    iaLoadingEl.classList.add('show');
    iaConteudoEl.innerHTML = '';
    iaPanelEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

    try {
        const resp = await fetch('../backend/api/noticias/analyze.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ noticias: todasNoticias.slice(0, 10) }),
        });
        const data = await resp.json();
        iaLoadingEl.classList.remove('show');
        renderAnaliseIA(data);
    } catch (err) {
        iaLoadingEl.classList.remove('show');
        iaConteudoEl.innerHTML = `<div class="sem-chave-card"><i class="bi bi-wifi-off" style="color:#f87171;"></i><h3>Erro</h3><p>${err.message}</p></div>`;
    } finally {
        btnAnalisar.disabled = false;
        btnAnalisar.innerHTML = '<i class="bi bi-arrow-repeat"></i>Reanalisar';
    }
});

/* ─── Render painel IA ─────────────────────────────────────────────────────── */
function renderAnaliseIA(data) {
    if (data.status === 'sem_chave') {
        iaConteudoEl.innerHTML = `<div class="sem-chave-card"><i class="bi bi-key-fill"></i><h3>Chave Gemini não configurada</h3><p>Adicione <code>GEMINI_API_KEY=sua_chave</code> no arquivo <code>.env</code>.</p></div>`;
        return;
    }
    if (data.status === 'error') {
        iaConteudoEl.innerHTML = `<div class="sem-chave-card"><i class="bi bi-exclamation-triangle-fill" style="color:#f87171;"></i><h3>Erro na análise</h3><p>${data.mensagem || 'Erro desconhecido.'}</p></div>`;
        return;
    }

    const nivelLabels = { alto: '⚠ Atenção Alta', medio: '● Atenção Moderada', baixo: '✓ Cenário Estável' };
    const nivel = data.nivel_alerta || 'baixo';
    const qtdRel = (data.analises || []).filter(a => a.relevante_para_usuario).length;

    const analisesHTML = (data.analises || []).map((a, idx) => {
        const acoes = (a.acoes_praticas || []).map(ac =>
            `<div class="ia-acao-item"><i class="bi bi-check2-circle"></i><span>${ac}</span></div>`
        ).join('');

        return `
        <div class="ia-analise-item" id="analise-${idx}">
            <button class="ia-analise-toggle" onclick="toggleAnalise(${idx})">
                <div style="display:flex;align-items:center;gap:8px;flex:1;overflow:hidden;">
                    ${badgeImpacto(a.impacto)}${badgeCategoria(a.categoria)}
                    ${a.relevante_para_usuario ? `<div class="badge-destaque-usuario" style="width:auto;margin-left:4px;"><i class="bi bi-bell-fill"></i>Afeta você</div>` : ''}
                </div>
                <span class="titulo-noticia">${a.titulo_noticia}</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="ia-analise-body">
                ${a.cenario_hipotetico ? `<div class="ia-cenario-hipotetico"><i class="bi bi-eye me-1"></i>${a.cenario_hipotetico}</div>` : ''}
                <p class="ia-como-afeta">${a.como_afeta || ''}</p>
                ${acoes ? `<div class="ia-acoes-praticas">${acoes}</div>` : ''}
                <div class="ia-detail-row" style="margin-top:12px;">
                    <div class="ia-detail-box investimento"><div class="label"><i class="bi bi-graph-up-arrow"></i>Sugestão</div><div class="text">${a.sugestao_investimento || ''}</div></div>
                    <div class="ia-detail-box economia"><div class="label"><i class="bi bi-piggy-bank"></i>Dica</div><div class="text">${a.dica_economia || ''}</div></div>
                </div>
            </div>
        </div>`;
    }).join('');

    iaConteudoEl.innerHTML = `
        <div class="ia-panel-header">
            <h2><i class="bi bi-stars"></i>Análise Personalizada</h2>
            <span class="alerta-nivel ${nivel}">${nivelLabels[nivel] || nivel}</span>
        </div>
        ${qtdRel > 0 ? `<div style="padding:0 26px;"><div class="destaque-info-bar"><i class="bi bi-bell-fill"></i><span><strong>${qtdRel} notícia${qtdRel > 1 ? 's' : ''}</strong> afeta${qtdRel > 1 ? 'm' : ''} suas despesas!</span></div></div>` : ''}
        <div class="ia-resumo-geral">${data.resumo_geral || ''}</div>
        ${analisesHTML}
        ${data.top_acao_da_semana ? `<div class="top-acao"><strong><i class="bi bi-lightning-charge-fill me-1"></i>Ação Prioritária</strong>${data.top_acao_da_semana}</div>` : ''}`;
}

function toggleAnalise(idx) {
    document.getElementById(`analise-${idx}`)?.classList.toggle('open');
}
window.toggleAnalise = toggleAnalise;
