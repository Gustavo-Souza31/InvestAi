// Lógica dos gráficos do Dashboard (Chart.js)
// Responsável por:
// - Buscar dados do relatório por período e data específica
// - Renderizar gráfico de linha (evolução) e rosca (proporção ou categoria)
// - Gerenciar filtros de período, categoria e datas específicas

let graficoLinha = null;
let graficoRosca = null;
let graficoCategoria = null;

const mesesAno = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

// ======================== API FETCHERS ========================

async function carregarRelatorio(periodo, categoriaId, ano, intervalo, comparacao) {
    let url = `${BASE_PATH}/backend/api/dashboard/relatorio.php?periodo=${periodo}`;
    if (categoriaId) url += `&categoria_id=${categoriaId}`;
    if (ano) url += `&ano=${ano}`;
    if (intervalo) url += `&intervalo=${intervalo}`;
    if (comparacao) url += `&comparacao=${comparacao}`;
    
    const resultado = await fetch(url);
    return await resultado.json();
}

async function carregarRelatorioCategoriaAPI(periodo, ano, intervalo) {
    let url = `${BASE_PATH}/backend/api/relatorios/despesas_categoria.php?periodo=${periodo}`;
    if (ano) url += `&ano=${ano}`;
    if (intervalo) url += `&intervalo=${intervalo}`;
    
    const resultado = await fetch(url);
    return await resultado.json();
}

async function buscarCategoriasDespesa() {
    try {
        const res = await fetch(`${BASE_PATH}/backend/api/categorias/read.php?tipo=despesa`);
        const data = await res.json();
        if (data.status === 'success') {
            const select = document.getElementById('filtro-categoria-dashboard');
            data.categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.nome;
                select.appendChild(option);
            });
        }
    } catch (e) {
        console.error("Erro ao carregar categorias", e);
    }
}


// ======================== FILTROS DE DATA ========================

function atualizarSelectsData(periodo) {
    const container = document.getElementById('container-intervalos');
    const selectIntervalo = document.getElementById('select-intervalo');
    const selectAno = document.getElementById('select-ano');
    
    container.style.display = 'flex';
    selectIntervalo.innerHTML = '';
    selectIntervalo.style.display = 'block';

    const hoje = new Date();
    const anoAtual = hoje.getFullYear();
    const mesAtual = hoje.getMonth() + 1; // 1-12

    // Preencher Anos
    if (selectAno.options.length === 0) {
        for (let i = anoAtual; i >= anoAtual - 5; i--) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = i;
            selectAno.appendChild(opt);
        }
    }

    if (periodo === '1m') {
        mesesAno.forEach((mes, idx) => {
            const opt = document.createElement('option');
            opt.value = idx + 1;
            opt.textContent = mes;
            if (idx + 1 === mesAtual) opt.selected = true;
            selectIntervalo.appendChild(opt);
        });
    } else if (periodo === '3m') {
        for (let i = 1; i <= 4; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = `${i}º Trimestre`;
            if (Math.ceil(mesAtual / 3) === i) opt.selected = true;
            selectIntervalo.appendChild(opt);
        }
    } else if (periodo === '6m') {
        for (let i = 1; i <= 2; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = `${i}º Semestre`;
            if (Math.ceil(mesAtual / 6) === i) opt.selected = true;
            selectIntervalo.appendChild(opt);
        }
    } else if (periodo === '1a') {
        selectIntervalo.style.display = 'none';
        const opt = document.createElement('option');
        opt.value = '1';
        opt.selected = true;
        selectIntervalo.appendChild(opt);
    }
}


// ======================== RENDERIZAÇÃO GERAL ========================

function mostrarEmptyStateGeral(containerHtml, message) {
    const msg = message || "Nenhum dado encontrado para o período selecionado.";
    document.getElementById(containerHtml).innerHTML = `
        <div class="text-center text-muted py-5" style="width: 100%; grid-column: 1 / -1;">
            <i class="bi bi-inbox text-secondary" style="font-size: 3rem;"></i>
            <h5 class="mt-3 text-light">Sem Movimentações</h5>
            <p>${msg}</p>
        </div>
    `;
}

function renderizarGraficoLinha(dados) {
    const wrapper = document.getElementById('linha-wrapper-geral');
    if (!dados.ganhos || (dados.ganhos.every(v => v === 0) && dados.despesas.every(v => v === 0))) {
        mostrarEmptyStateGeral('linha-wrapper-geral', 'Não há ganhos ou despesas no período selecionado.');
        return;
    }

    wrapper.innerHTML = '<canvas id="grafico-linha"></canvas>';
    const ctx = document.getElementById('grafico-linha').getContext('2d');
    if (graficoLinha) graficoLinha.destroy();

    graficoLinha = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dados.labels,
            datasets: [
                {
                    label: 'Ganhos',
                    data: dados.ganhos,
                    borderColor: '#66bb6a',
                    backgroundColor: 'rgba(102, 187, 106, 0.08)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#66bb6a',
                    pointBorderColor: '#66bb6a',
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Despesas',
                    data: dados.despesas,
                    borderColor: '#ef9a9a',
                    backgroundColor: 'rgba(239, 154, 154, 0.08)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#ef9a9a',
                    pointBorderColor: '#ef9a9a',
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    display: true, position: 'top', align: 'end',
                    labels: { color: '#94a3b8', font: { family: 'Outfit', size: 12, weight: '600' }, usePointStyle: true, padding: 16 }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)', titleColor: '#f8fafc', bodyColor: '#94a3b8',
                    borderColor: 'rgba(255, 255, 255, 0.1)', borderWidth: 1, padding: 12, cornerRadius: 10,
                    callbacks: { label: function(context) { return context.dataset.label + ': ' + formatMoney(context.raw); } }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(255, 255, 255, 0.04)', drawBorder: false }, ticks: { color: '#555', font: { family: 'Outfit', size: 11 } } },
                y: { grid: { color: 'rgba(255, 255, 255, 0.04)', drawBorder: false }, ticks: { color: '#555', font: { family: 'Outfit', size: 11 }, callback: function(value) { return value >= 1000 ? 'R$ ' + (value / 1000).toFixed(1) + 'k' : 'R$ ' + value; } }, beginAtZero: true }
            }
        }
    });
}

function renderizarGraficoRosca(dados) {
    const total = dados.total_ganhos + dados.total_despesas;
    
    // Elementos da interface
    const container = document.getElementById('rosca-wrapper-geral');
    
    // Sempre atualizar legendas e saldo, mesmo zerado
    const percGanhos = total > 0 ? ((dados.total_ganhos / total) * 100).toFixed(1) : 0;
    const percDespesas = total > 0 ? ((dados.total_despesas / total) * 100).toFixed(1) : 0;
    document.getElementById('legenda-ganhos-valor').textContent = formatMoney(dados.total_ganhos);
    document.getElementById('legenda-despesas-valor').textContent = formatMoney(dados.total_despesas);
    document.getElementById('legenda-ganhos-perc').textContent = percGanhos + '%';
    document.getElementById('legenda-despesas-perc').textContent = percDespesas + '%';
    
    const saldo = dados.total_ganhos - dados.total_despesas;
    const saldoEl = document.getElementById('saldo-periodo');
    saldoEl.textContent = formatMoney(saldo);
    saldoEl.className = saldo >= 0 ? 'chart-saldo positivo' : 'chart-saldo negativo';

    if (total === 0) {
        // Se zerado, não renderiza a rosca no DOM
        const canvasWrapper = container.querySelector('.chart-doughnut-canvas');
        if(canvasWrapper) canvasWrapper.innerHTML = '<div style="height:100%; display:flex; align-items:center; justify-content:center; color:#555;">Sem dados</div>';
        return;
    } else {
        // Restaurar canvas se foi removido
        const canvasWrapper = container.querySelector('.chart-doughnut-canvas');
        if(canvasWrapper) canvasWrapper.innerHTML = '<canvas id="grafico-rosca"></canvas>';
    }

    const ctx = document.getElementById('grafico-rosca').getContext('2d');
    if (graficoRosca) graficoRosca.destroy();

    graficoRosca = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Ganhos', 'Despesas'],
            datasets: [{
                data: [dados.total_ganhos || 0, dados.total_despesas || 0],
                backgroundColor: ['rgba(102, 187, 106, 0.85)', 'rgba(239, 154, 154, 0.85)'],
                borderColor: ['rgba(102, 187, 106, 1)', 'rgba(239, 154, 154, 1)'],
                borderWidth: 2, hoverBackgroundColor: ['rgba(102, 187, 106, 1)', 'rgba(239, 154, 154, 1)'],
                hoverBorderWidth: 3, spacing: 4, borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)', titleColor: '#f8fafc', bodyColor: '#94a3b8',
                    borderColor: 'rgba(255, 255, 255, 0.1)', borderWidth: 1, padding: 12, cornerRadius: 10,
                    callbacks: { label: function(context) { const perc = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0; return context.label + ': ' + formatMoney(context.raw) + ' (' + perc + '%)'; } }
                }
            }
        }
    });
}

function renderizarComparativo(dados) {
    const formatValue = (val) => {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
    };

    const atualizarBadge = (tipo, atual, anterior, badgeId) => {
        const badge = document.getElementById(badgeId);
        if (!badge) return;

        // Se o anterior for 0, não há histórico suficiente para calcular porcentagem
        if (anterior === 0) {
            badge.innerHTML = '<span style="color: var(--text-muted); font-size: 0.8rem;">Sem histórico</span>';
            badge.style.background = 'transparent';
            return;
        }

        const diferenca = atual - anterior;
        const perc = ((diferenca / anterior) * 100).toFixed(1);
        
        let icon = '';
        let colorClass = '';
        let bgColor = '';

        if (diferenca > 0) {
            icon = '<i class="bi bi-arrow-up-right"></i>';
            // Aumento de ganho é bom (verde), aumento de despesa é ruim (vermelho)
            colorClass = tipo === 'ganho' ? '#4ade80' : '#f87171';
            bgColor = tipo === 'ganho' ? 'rgba(74, 222, 128, 0.1)' : 'rgba(248, 113, 113, 0.1)';
        } else if (diferenca < 0) {
            icon = '<i class="bi bi-arrow-down-right"></i>';
            // Redução de ganho é ruim (vermelho), redução de despesa é bom (verde)
            colorClass = tipo === 'ganho' ? '#f87171' : '#4ade80';
            bgColor = tipo === 'ganho' ? 'rgba(248, 113, 113, 0.1)' : 'rgba(74, 222, 128, 0.1)';
        } else {
            icon = '<i class="bi bi-dash"></i>';
            colorClass = '#94a3b8';
            bgColor = 'rgba(148, 163, 184, 0.1)';
        }

        badge.innerHTML = `<span style="color: ${colorClass};">${icon} ${perc > 0 ? '+' : ''}${perc}%</span> <span style="color: var(--text-muted); font-size: 0.75rem; margin-left: 4px; font-weight: 400;">vs ant.</span>`;
        badge.style.background = bgColor;
    };

    // Atualiza os totais principais (que já estavam definidos em HTML)
    const elTotalGanhos = document.getElementById('comp-total-ganhos');
    const elTotalDespesas = document.getElementById('comp-total-despesas');
    
    if (elTotalGanhos) elTotalGanhos.textContent = formatValue(dados.total_ganhos);
    if (elTotalDespesas) elTotalDespesas.textContent = formatValue(dados.total_despesas);

    atualizarBadge('ganho', dados.total_ganhos, dados.total_ganhos_anterior, 'badge-ganhos');
    atualizarBadge('despesa', dados.total_despesas, dados.total_despesas_anterior, 'badge-despesas');
    
    // Mostra o container que estava oculto ou em estado inicial
    const compContainer = document.getElementById('comparative-summary-container');
    if (compContainer) compContainer.style.display = 'flex';
}

// ======================== RENDERIZAÇÃO CATEGORIAS ========================

function renderizarGraficoCategoria(dados) {
    const container = document.getElementById('rosca-wrapper-cat');
    if (!container) return;

    const canvasWrapper = container.querySelector('.chart-doughnut-canvas');
    if (!canvasWrapper) return;
    
    if (dados.labels.length === 0) {
        canvasWrapper.innerHTML = '<div style="height:100%; display:flex; align-items:center; justify-content:center; color:#555;">Sem dados de despesa</div>';
        return;
    } else {
        canvasWrapper.innerHTML = '<canvas id="grafico-categoria"></canvas>';
    }

    const ctx = document.getElementById('grafico-categoria').getContext('2d');
    if (graficoCategoria) graficoCategoria.destroy();

    const baseColors = ['#ef9a9a', '#f48fb1', '#ce93d8', '#b39ddb', '#9fa8da', '#90caf9', '#81d4fa', '#80cbc4', '#a5d6a7', '#c5e1a5', '#e6ee9c', '#fff59d', '#ffe082', '#ffcc80', '#ffab91'];
    const colors = dados.labels.map((_, i) => baseColors[i % baseColors.length]);

    graficoCategoria = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dados.labels,
            datasets: [{
                data: dados.valores,
                backgroundColor: colors,
                borderColor: 'rgba(15, 23, 42, 0.5)',
                borderWidth: 2, hoverOffset: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)', titleColor: '#f8fafc', bodyColor: '#94a3b8',
                    borderColor: 'rgba(255, 255, 255, 0.1)', borderWidth: 1, padding: 12, cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            const val = context.raw;
                            const perc = dados.total_geral > 0 ? ((val / dados.total_geral) * 100).toFixed(1) : 0;
                            return `${context.label}: ${formatMoney(val)} (${perc}%)`;
                        }
                    }
                }
            }
        }
    });
}

function renderizarListaCategoria(dados) {
    const container = document.getElementById('cat-lista-container');
    if (!container) return;
    container.innerHTML = '';

    const baseColors = ['#ef9a9a', '#f48fb1', '#ce93d8', '#b39ddb', '#9fa8da', '#90caf9', '#81d4fa', '#80cbc4', '#a5d6a7', '#c5e1a5', '#e6ee9c', '#fff59d', '#ffe082', '#ffcc80', '#ffab91'];

    if (dados.labels.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1"></i><p class="mt-2">Nenhuma despesa no período selecionado.</p></div>';
        return;
    }

    dados.labels.forEach((label, index) => {
        const valor = dados.valores[index];
        const perc = dados.total_geral > 0 ? ((valor / dados.total_geral) * 100).toFixed(1) : 0;
        const color = baseColors[index % baseColors.length];

        const item = document.createElement('div');
        item.className = 'd-flex justify-content-between align-items-center mb-3';
        item.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background-color:${color};"></span>
                <span style="font-size: 0.95rem; color: var(--text-main); font-weight: 500;">${label}</span>
            </div>
            <div class="text-end">
                <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-main);">${formatMoney(valor)}</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">${perc}%</div>
            </div>
        `;
        container.appendChild(item);
    });
}


// ======================== ORQUESTRAÇÃO ========================

async function atualizarGraficos(periodoBtn) {
    const chartLoading = document.getElementById('charts-loading');
    const chartContent = document.getElementById('charts-content');
    
    const categoriaSelect = document.getElementById('filtro-categoria-dashboard');
    const categoriaVal = categoriaSelect ? categoriaSelect.value : '';
    
    const ano = document.getElementById('select-ano').value;
    const intervalo = document.getElementById('select-intervalo').value;

    chartLoading.style.display = 'flex';
    chartContent.style.display = 'none';

    try {
        if (categoriaVal === 'todas') {
            // Modo Visão por Categorias
            document.getElementById('linha-wrapper-geral').style.display = 'none';
            document.getElementById('rosca-wrapper-geral').style.display = 'none';
            document.getElementById('linha-wrapper-cat').style.display = 'block';
            document.getElementById('rosca-wrapper-cat').style.display = 'block';
            
            document.getElementById('linha-titulo').innerHTML = '<i class="bi bi-list-ul"></i> Detalhamento';
            document.getElementById('rosca-titulo').innerHTML = '<i class="bi bi-pie-chart"></i> Distribuição';

            // Oculta os comparativos gerais pois não fazem sentido na visão específica de categorias
            const compContainer = document.getElementById('comparative-summary-container');
            if (compContainer) compContainer.style.display = 'none';

            const dadosCat = await carregarRelatorioCategoriaAPI(periodoBtn, ano, intervalo);
            if (dadosCat.status === 'success') {
                renderizarGraficoCategoria(dadosCat);
                renderizarListaCategoria(dadosCat);
                document.getElementById('cat-total-geral').textContent = formatMoney(dadosCat.total_geral);
            } else {
                mostrarEmptyStateGeral('linha-wrapper-cat', 'Não foi possível carregar as despesas.');
            }
        } else {
            // Modo Resumo Geral ou Filtrado por 1 Categoria
            document.getElementById('linha-wrapper-cat').style.display = 'none';
            document.getElementById('rosca-wrapper-cat').style.display = 'none';
            document.getElementById('linha-wrapper-geral').style.display = 'block';
            document.getElementById('rosca-wrapper-geral').style.display = 'block';
            
            document.getElementById('linha-titulo').innerHTML = '<i class="bi bi-graph-up"></i> Evolução Mensal';
            document.getElementById('rosca-titulo').innerHTML = '<i class="bi bi-pie-chart"></i> Proporção';

            const comparacaoVal = document.getElementById('tipo-comparacao') ? document.getElementById('tipo-comparacao').value : 'yoy';

            const dados = await carregarRelatorio(periodoBtn, categoriaVal, ano, intervalo, comparacaoVal);
            if (dados.status === 'success') {
                renderizarComparativo(dados);
                renderizarGraficoLinha(dados);
                renderizarGraficoRosca(dados);
            } else {
                mostrarEmptyStateGeral('linha-wrapper-geral', 'Não foi possível carregar o relatório.');
            }
        }

        chartLoading.style.display = 'none';
        chartContent.style.display = 'grid';
    } catch (error) {
        console.error('Erro ao carregar relatório:', error);
        chartLoading.innerHTML = '<p style="color: #f87171;">Erro ao carregar relatório</p>';
    }
}

// Inicializar
async function inicializarGraficos() {
    await buscarCategoriasDespesa();

    const botoes = document.querySelectorAll('.chart-filter-btn');
    const selectCategoria = document.getElementById('filtro-categoria-dashboard');
    const selectAno = document.getElementById('select-ano');
    const selectIntervalo = document.getElementById('select-intervalo');
    const selectComparacao = document.getElementById('tipo-comparacao');

    // Inicializa selects de data com o botão padrão (3 meses)
    atualizarSelectsData('3m');

    const changeListener = () => {
        const activeBtn = document.querySelector('.chart-filter-btn.active');
        if (activeBtn) atualizarGraficos(activeBtn.dataset.periodo);
    };

    botoes.forEach(botao => {
        botao.addEventListener('click', () => {
            botoes.forEach(b => b.classList.remove('active'));
            botao.classList.add('active');
            
            atualizarSelectsData(botao.dataset.periodo);
            atualizarGraficos(botao.dataset.periodo);
            
            if (typeof inicializar === 'function') {
                inicializar(botao.dataset.periodo, true);
            }
        });
    });

    if (selectCategoria) selectCategoria.addEventListener('change', changeListener);
    if (selectAno) selectAno.addEventListener('change', changeListener);
    if (selectIntervalo) selectIntervalo.addEventListener('change', changeListener);
    if (selectComparacao) selectComparacao.addEventListener('change', changeListener);

    // Carregar padrão inicial
    atualizarGraficos('3m');
}

document.addEventListener('DOMContentLoaded', inicializarGraficos);
