let graficoCategoria = null;

function popularSelectAnos(selectAno) {
    const anoAtual = new Date().getFullYear();
    selectAno.innerHTML = '';
    for (let i = anoAtual + 1; i >= anoAtual - 5; i--) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        if (i === anoAtual) option.selected = true;
        selectAno.appendChild(option);
    }
}

function atualizarSelectIntervalo(tipo, selectIntervalo, containerIntervalo) {
    selectIntervalo.innerHTML = '';
    let mostrar = true;

    if (tipo === '1m') {
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        const mesAtual = new Date().getMonth() + 1; // 1-12
        meses.forEach((mes, index) => {
            const val = index + 1;
            const option = document.createElement('option');
            option.value = val;
            option.textContent = mes;
            if (val === mesAtual) option.selected = true;
            selectIntervalo.appendChild(option);
        });
    } else if (tipo === '3m') {
        const mesAtual = new Date().getMonth() + 1;
        const triAtual = Math.ceil(mesAtual / 3);
        for (let i = 1; i <= 4; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${i}º Trimestre`;
            if (i === triAtual) option.selected = true;
            selectIntervalo.appendChild(option);
        }
    } else if (tipo === '6m') {
        const mesAtual = new Date().getMonth() + 1;
        const semAtual = Math.ceil(mesAtual / 6);
        for (let i = 1; i <= 2; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${i}º Semestre`;
            if (i === semAtual) option.selected = true;
            selectIntervalo.appendChild(option);
        }
    } else if (tipo === '1a') {
        mostrar = false;
        // envia valor 1 para que a API receba um número válido
        const option = document.createElement('option');
        option.value = 1;
        option.selected = true;
        selectIntervalo.appendChild(option);
    }

    if (mostrar) {
        containerIntervalo.style.display = 'block';
    } else {
        containerIntervalo.style.display = 'none';
    }
}

async function carregarRelatorioCategoria() {
    const activeBtn = document.querySelector('.btn-filtro-cat.active');
    if (!activeBtn) return;
    
    const tipo = activeBtn.dataset.periodo;
    const ano = document.getElementById('select-cat-ano').value;
    const intervalo = document.getElementById('select-cat-intervalo').value;

    const chartLoading = document.getElementById('cat-charts-loading');
    const chartContent = document.getElementById('cat-charts-content');
    const emptyState = document.getElementById('cat-empty-state');

    chartLoading.style.display = 'flex';
    chartContent.style.display = 'none';
    emptyState.style.display = 'none';

    try {
        const resposta = await fetch(`${BASE_PATH}/backend/api/relatorios/despesas_categoria.php?tipo=${tipo}&ano=${ano}&intervalo=${intervalo}`);
        const resultado = await resposta.json();

        if (resultado.status === 'success') {
            chartLoading.style.display = 'none';
            if (resultado.total_geral > 0) {
                renderizarGraficoCategoria(resultado);
                renderizarListaCategoria(resultado);

                const saldoEl = document.getElementById('cat-total-geral');
                if (saldoEl) {
                    saldoEl.textContent = formatMoney(resultado.total_geral);
                }

                chartContent.style.display = 'grid';
            } else {
                emptyState.style.display = 'block';
            }
        } else {
            chartLoading.innerHTML = '<p style="color: #f87171;">Erro ao carregar relatório</p>';
        }
    } catch (error) {
        console.error('Erro ao carregar relatório de categoria:', error);
        chartLoading.innerHTML = '<p style="color: #f87171;">Erro ao carregar relatório</p>';
    }
}

function renderizarGraficoCategoria(dados) {
    const canvas = document.getElementById('grafico-categoria');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');

    if (graficoCategoria) {
        graficoCategoria.destroy();
    }

    const baseColors = [
        '#ef9a9a', '#f48fb1', '#ce93d8', '#b39ddb', '#9fa8da', 
        '#90caf9', '#81d4fa', '#80cbc4', '#a5d6a7', '#c5e1a5', 
        '#e6ee9c', '#fff59d', '#ffe082', '#ffcc80', '#ffab91'
    ];
    
    const colors = dados.labels.map((_, i) => baseColors[i % baseColors.length]);

    graficoCategoria = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dados.labels,
            datasets: [{
                data: dados.valores,
                backgroundColor: colors,
                borderColor: 'rgba(15, 23, 42, 0.5)',
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#f8fafc',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    titleFont: { family: 'Outfit', size: 13, weight: '700' },
                    bodyFont: { family: 'Outfit', size: 12 },
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

    const baseColors = [
        '#ef9a9a', '#f48fb1', '#ce93d8', '#b39ddb', '#9fa8da', 
        '#90caf9', '#81d4fa', '#80cbc4', '#a5d6a7', '#c5e1a5', 
        '#e6ee9c', '#fff59d', '#ffe082', '#ffcc80', '#ffab91'
    ];

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

document.addEventListener('DOMContentLoaded', () => {
    const selectAno = document.getElementById('select-cat-ano');
    const selectIntervalo = document.getElementById('select-cat-intervalo');
    const containerIntervalo = document.getElementById('container-cat-intervalo');
    const btnGerar = document.getElementById('btn-gerar-cat');
    const botoes = document.querySelectorAll('.btn-filtro-cat');

    if (!selectAno || !selectIntervalo || !btnGerar || botoes.length === 0) return;

    popularSelectAnos(selectAno);

    let activeBtn = document.querySelector('.btn-filtro-cat.active');
    if (!activeBtn) {
        activeBtn = botoes[0];
        activeBtn.classList.add('active');
    }
    atualizarSelectIntervalo(activeBtn.dataset.periodo, selectIntervalo, containerIntervalo);

    botoes.forEach(btn => {
        btn.addEventListener('click', () => {
            botoes.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            atualizarSelectIntervalo(btn.dataset.periodo, selectIntervalo, containerIntervalo);
        });
    });

    btnGerar.addEventListener('click', carregarRelatorioCategoria);

    carregarRelatorioCategoria();
});
