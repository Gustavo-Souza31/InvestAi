// ===== CONSTANTES =====

const ORC_ICONS = {
    'Salário':                '💰',
    'Freelance':              '🎯',
    'Investimentos':          '📈',
    'Alimentação':            '🍽️',
    'Transporte':             '🚗',
    'Habitação':              '🏠',
    'Saúde':                  '💊',
    'Educação':               '📚',
    'Entretenimento':         '🎬',
    'Vestuário e Acessórios': '👕',
    'Utilidades Domésticas':  '💡',
    'Outros Gastos':          '📦',
};

// Modo atual do modal: 'create' para novo, 'edit' para edição
let _orcModo = 'create';

// ===== RENDER =====

function renderizarOrcamentos(orcamentos) {
    const grid  = document.getElementById('orcamento-grid');
    const empty = document.getElementById('orcamento-empty');

    if (!orcamentos || orcamentos.length === 0) {
        empty.style.display = 'flex';
        grid.querySelectorAll('.orc-card').forEach(c => c.remove());
        return;
    }

    empty.style.display = 'none';
    grid.querySelectorAll('.orc-card').forEach(c => c.remove());

    orcamentos.forEach(orc => {
        const pct      = orc.percentual;
        const cor      = pct >= 100 ? 'var(--color-expense)' : pct >= 80 ? '#fbbf24' : 'var(--color-gain)';
        const icone    = ORC_ICONS[orc.categoria] ?? '📊';
        const gasto    = formatMoney(orc.gasto_atual);
        const limite   = formatMoney(orc.limite);
        const status   = pct >= 100 ? '⚠️ Limite atingido!' : pct >= 80 ? '⚡ Quase no limite' : '✅ Dentro do limite';

        const card = document.createElement('div');
        card.className = 'orc-card';
        card.innerHTML = `
            <div class="orc-card-top">
                <span class="orc-card-icon">${icone}</span>
                <div class="orc-card-info">
                    <span class="orc-card-nome">${orc.categoria}</span>
                    <span class="orc-card-status" style="color:${cor}">${status}</span>
                </div>
                <div class="orc-card-buttons">
                    <button class="orc-edit-btn" onclick="abrirModalOrcamentoEdicao(${orc.categoria_id}, '${orc.categoria}', ${orc.limite})" title="Editar limite">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="orc-delete-btn" onclick="abrirModalDeleteOrcamento(${orc.categoria_id}, '${orc.categoria}')" title="Deletar orçamento">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>
            <div class="orc-progress-wrap">
                <div class="orc-progress-bar" style="width: ${pct}%; background: ${cor};"></div>
            </div>
            <div class="orc-card-valores">
                <span>Gasto: <strong>${gasto}</strong></span>
                <span>${pct}% de <strong>${limite}</strong></span>
            </div>
        `;
        grid.appendChild(card);
    });
}

// ===== LOAD =====

async function carregarOrcamentos() {
    try {
        const resultado = await listarOrcamentos();
        if (resultado.status === 'success') {
            renderizarOrcamentos(resultado.orcamentos);
        }
    } catch (e) {
        console.error('Erro ao carregar orçamentos:', e);
    }
}

// ===== MODAL =====

function abrirModalOrcamento() {
    _orcModo = 'create';
    document.getElementById('orc-categoria').value = '';
    document.getElementById('orc-limite').value    = '';
    ocultarAlertOrc();
    carregarCategoriasNoModal();
    document.getElementById('orcamento-overlay').classList.add('active');
    document.getElementById('orc-categoria').focus();
}

function fecharModalOrcamento() {
    document.getElementById('orcamento-overlay').classList.remove('active');
}

// Ponto único chamado pelo onclick do botão Salvar no HTML
function salvarOrcamento() {
    if (_orcModo === 'edit') {
        atualizarOrcamento();
    } else {
        criarOrcamento();
    }
}

// ===== ALERT INTERNO DO MODAL =====

function mostrarAlertOrc(msg, tipo) {
    const el = document.getElementById('orc-alert');
    el.textContent = msg;
    el.className   = `orc-alert orc-alert-${tipo}`;
    el.style.display = 'block';
}

function ocultarAlertOrc() {
    const el = document.getElementById('orc-alert');
    el.style.display = 'none';
}

// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('orcamento-overlay')?.addEventListener('click', e => {
        if (e.target.id === 'orcamento-overlay') fecharModalOrcamento();
    });

    document.getElementById('orc-modal-delete')?.addEventListener('click', e => {
        if (e.target.id === 'orc-modal-delete') fecharModalDeleteOrcamento();
    });

    document.getElementById('orc-btn-confirm-delete')?.addEventListener('click', confirmarDeleteOrcamento);

    carregarOrcamentos();
});
