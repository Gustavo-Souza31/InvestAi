/**
 * Front/api/orcamento/orcamento.js
 * Lógica completa do planejamento de orçamento por categoria.
 */

const ORC_API_READ = '../backend/api/orcamento/read.php';
const ORC_API_SAVE = '../backend/api/orcamento/save.php';
const CAT_API_DESPESA = '../backend/api/categorias/read.php?tipo=despesa';

// Emojis por categoria para visual no card
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

// ─── Carregar categorias de despesa e popular o select ──────────────────────
async function carregarCategoriasNoModal() {
    try {
        const res = await fetch(CAT_API_DESPESA);
        const data = await res.json();
        
        if (data.status === 'success' && data.categorias) {
            const select = document.getElementById('orc-categoria');
            
            // Preservar o valor anterior se houver (para edição)
            const valorAnterior = select.value;
            
            // Limpar options existentes (mantém o placeholder)
            select.innerHTML = '<option value="">Selecione uma categoria...</option>';
            
            // Adicionar categorias
            data.categorias.forEach(cat => {
                const icon = ORC_ICONS[cat.nome] || '📁';
                const option = document.createElement('option');
                option.value = cat.nome;
                option.textContent = `${icon} ${cat.nome}`;
                select.appendChild(option);
            });
            
            // Restaurar valor anterior se existia
            if (valorAnterior) {
                select.value = valorAnterior;
            }
        }
    } catch (e) {
        console.error('Erro ao carregar categorias:', e);
    }
}

// ─── Carregar e renderizar orçamentos ────────────────────────────────────────
async function carregarOrcamentos() {
    try {
        const res  = await fetch(ORC_API_READ);
        const data = await res.json();
        if (data.status === 'success') {
            renderizarOrcamentos(data.orcamentos);
        }
    } catch (e) {
        console.error('Erro ao carregar orçamentos:', e);
    }
}

function renderizarOrcamentos(orcamentos) {
    const grid  = document.getElementById('orcamento-grid');
    const empty = document.getElementById('orcamento-empty');

    if (!orcamentos || orcamentos.length === 0) {
        empty.style.display = 'flex';
        // Remove cards antigos se houver
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
                <button class="orc-edit-btn" onclick="abrirModalOrcamentoEdicao('${orc.categoria}', ${orc.limite})" title="Editar limite">
                    <i class="bi bi-pencil-fill"></i>
                </button>
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

// ─── Modal ────────────────────────────────────────────────────────────────────
function abrirModalOrcamento() {
    document.getElementById('orc-categoria').value = '';
    document.getElementById('orc-limite').value    = '';
    ocultarAlertOrc();
    carregarCategoriasNoModal();
    document.getElementById('orcamento-overlay').classList.add('active');
    document.getElementById('orc-categoria').focus();
}

function abrirModalOrcamentoEdicao(categoria, limite) {
    document.getElementById('orc-categoria').value = categoria;
    document.getElementById('orc-limite').value    = limite;
    ocultarAlertOrc();
    carregarCategoriasNoModal();
    document.getElementById('orcamento-overlay').classList.add('active');
    document.getElementById('orc-limite').focus();
}

function fecharModalOrcamento() {
    document.getElementById('orcamento-overlay').classList.remove('active');
}

// ─── Validação e Salvar ───────────────────────────────────────────────────────
async function salvarOrcamento() {
    const categoria = document.getElementById('orc-categoria').value.trim();
    const limiteStr = document.getElementById('orc-limite').value.trim();
    const limite    = parseFloat(limiteStr);

    // Validações
    if (!categoria) {
        mostrarAlertOrc('Selecione uma categoria de despesa.', 'erro');
        return;
    }
    if (limiteStr === '' || isNaN(limite)) {
        mostrarAlertOrc('Informe um valor numérico válido.', 'erro');
        return;
    }
    if (limite <= 0) {
        mostrarAlertOrc('O limite deve ser maior que zero.', 'erro');
        return;
    }

    const btn = document.getElementById('orc-btn-salvar');
    btn.disabled = true;
    btn.innerHTML = '<div class="orc-spinner"></div> Salvando...';

    try {
        const res  = await fetch(ORC_API_SAVE, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ categoria, limite }),
        });
        const data = await res.json();

        if (data.status === 'success') {
            fecharModalOrcamento();
            showAlert('Limite definido com sucesso! 🎯', 'success');
            carregarOrcamentos(); // Atualiza as barras de progresso
        } else {
            mostrarAlertOrc(data.message || 'Erro ao salvar.', 'erro');
        }
    } catch (e) {
        mostrarAlertOrc('Erro de conexão. Tente novamente.', 'erro');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-all me-1"></i>Salvar Limite';
    }
}

// ─── Alert interno do modal ───────────────────────────────────────────────────
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

// ─── Fechar ao clicar fora ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('orcamento-overlay')?.addEventListener('click', e => {
        if (e.target.id === 'orcamento-overlay') fecharModalOrcamento();
    });
    carregarOrcamentos();
});
