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

// ===== ALERTA DO MODAL =====

/**
 * Exibe mensagem de erro ou sucesso DENTRO do modal de orçamento.
 * Diferente do showAlert() global que procura .alert-message (que não existe no dashboard),
 * essa função usa o <div id="orc-alert"> que já está no HTML do modal.
 *
 * @param {string} msg  — Texto da mensagem
 * @param {string} type — 'error' ou 'success'
 */
function showOrcAlert(msg, type = 'error') {
    const el = document.getElementById('orc-alert');
    if (!el) return;

    el.textContent = msg;
    el.className = 'orc-alert ' + type;
    el.style.display = 'block';

    // Esconde automaticamente após 5 segundos
    clearTimeout(el._timeout);
    el._timeout = setTimeout(() => { el.style.display = 'none'; }, 5000);
}

/** Esconde o alerta do modal de orçamento */
function hideOrcAlert() {
    const el = document.getElementById('orc-alert');
    if (el) {
        el.style.display = 'none';
        el.textContent = '';
    }
}

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
                    <button class="orc-delete-btn" onclick="abrirExclusaoOrcamento(${orc.categoria_id}, '${orc.categoria}')" title="Excluir orçamento">
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
    hideOrcAlert(); // Limpa erros de tentativas anteriores
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

// ===== INIT =====

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('orcamento-overlay')?.addEventListener('click', e => {
        if (e.target.id === 'orcamento-overlay') fecharModalOrcamento();
    });

    document.getElementById('orc-modal-delete')?.addEventListener('click', e => {
        if (e.target.id === 'orc-modal-delete') fecharExclusaoOrcamento();
    });

    document.getElementById('orc-btn-confirm-delete')?.addEventListener('click', excluirOrcamento);

    carregarOrcamentos();
});
