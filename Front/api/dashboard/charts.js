// Lógica dos gráficos do Dashboard (Chart.js)
// Responsável por:
// - Buscar dados do relatório por período
// - Renderizar gráfico de linha (evolução ganhos vs despesas)
// - Renderizar gráfico de rosca (proporção ganhos vs despesas)
// - Gerenciar filtros de período

let graficoLinha = null;
let graficoRosca = null;

async function carregarRelatorio(periodo) {
    const resultado = await fetch(`../backend/api/dashboard/relatorio.php?periodo=${periodo}`);
    return await resultado.json();
}

// Renderiza ou atualiza o gráfico de linha
function renderizarGraficoLinha(dados) {
    const ctx = document.getElementById('grafico-linha').getContext('2d');

    if (graficoLinha) {
        graficoLinha.destroy();
    }

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
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: '#94a3b8',
                        font: {
                            family: 'Outfit',
                            size: 12,
                            weight: '600'
                        },
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#f8fafc',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    titleFont: {
                        family: 'Outfit',
                        size: 13,
                        weight: '700'
                    },
                    bodyFont: {
                        family: 'Outfit',
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatMoney(context.raw);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.04)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#555',
                        font: {
                            family: 'Outfit',
                            size: 11
                        },
                        maxRotation: 0
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.04)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#555',
                        font: {
                            family: 'Outfit',
                            size: 11
                        },
                        callback: function(value) {
                            if (value >= 1000) {
                                return 'R$ ' + (value / 1000).toFixed(1) + 'k';
                            }
                            return 'R$ ' + value;
                        }
                    },
                    beginAtZero: true
                }
            }
        }
    });
}

// Renderiza ou atualiza o gráfico de rosca
function renderizarGraficoRosca(dados) {
    const ctx = document.getElementById('grafico-rosca').getContext('2d');

    if (graficoRosca) {
        graficoRosca.destroy();
    }

    const total = dados.total_ganhos + dados.total_despesas;
    const percGanhos = total > 0 ? ((dados.total_ganhos / total) * 100).toFixed(1) : 0;
    const percDespesas = total > 0 ? ((dados.total_despesas / total) * 100).toFixed(1) : 0;

    // Atualizar legendas customizadas
    document.getElementById('legenda-ganhos-valor').textContent = formatMoney(dados.total_ganhos);
    document.getElementById('legenda-despesas-valor').textContent = formatMoney(dados.total_despesas);
    document.getElementById('legenda-ganhos-perc').textContent = percGanhos + '%';
    document.getElementById('legenda-despesas-perc').textContent = percDespesas + '%';

    // Atualizar saldo do período
    const saldo = dados.total_ganhos - dados.total_despesas;
    const saldoEl = document.getElementById('saldo-periodo');
    saldoEl.textContent = formatMoney(saldo);
    saldoEl.className = saldo >= 0 ? 'chart-saldo positivo' : 'chart-saldo negativo';

    graficoRosca = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Ganhos', 'Despesas'],
            datasets: [{
                data: [dados.total_ganhos || 0, dados.total_despesas || 0],
                backgroundColor: [
                    'rgba(102, 187, 106, 0.85)',
                    'rgba(239, 154, 154, 0.85)'
                ],
                borderColor: [
                    'rgba(102, 187, 106, 1)',
                    'rgba(239, 154, 154, 1)'
                ],
                borderWidth: 2,
                hoverBackgroundColor: [
                    'rgba(102, 187, 106, 1)',
                    'rgba(239, 154, 154, 1)'
                ],
                hoverBorderWidth: 3,
                spacing: 4,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#f8fafc',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    titleFont: {
                        family: 'Outfit',
                        size: 13,
                        weight: '700'
                    },
                    bodyFont: {
                        family: 'Outfit',
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            const perc = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + formatMoney(context.raw) + ' (' + perc + '%)';
                        }
                    }
                }
            }
        }
    });
}


// Carrega dados e renderiza ambos os gráficos
async function atualizarGraficos(periodo) {
    const chartLoading = document.getElementById('charts-loading');
    const chartContent = document.getElementById('charts-content');

    // Mostrar loading
    chartLoading.style.display = 'flex';
    chartContent.style.display = 'none';

    try {
        const dados = await carregarRelatorio(periodo);

        if (dados.status === 'success') {
            renderizarGraficoLinha(dados);
            renderizarGraficoRosca(dados);

            // Mostrar conteúdo
            chartLoading.style.display = 'none';
            chartContent.style.display = 'grid';
        } else {
            chartLoading.innerHTML = '<p style="color: #f87171;">Erro ao carregar relatório</p>';
        }
    } catch (error) {
        console.error('Erro ao carregar relatório:', error);
        chartLoading.innerHTML = '<p style="color: #f87171;">Erro ao carregar relatório</p>';
    }
}


// Inicializar filtros e gráficos
function inicializarGraficos() {
    const botoes = document.querySelectorAll('.chart-filter-btn');

    botoes.forEach(botao => {
        botao.addEventListener('click', () => {
            // Atualizar estado ativo
            botoes.forEach(botaoItem => botaoItem.classList.remove('active'));
            botao.classList.add('active');

            // Carregar gráficos e resumo
            const periodo = botao.dataset.periodo;
            atualizarGraficos(periodo);
            
            if (typeof inicializar === 'function') {
                inicializar(periodo, true);
            }
        });
    });

    // Carregar com filtro padrão (3 meses)
    atualizarGraficos('3m');
}


// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', inicializarGraficos);
