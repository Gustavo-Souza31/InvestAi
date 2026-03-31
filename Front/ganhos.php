<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$nome = htmlspecialchars($_SESSION['usuario_nome']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestAi — Meus Ganhos</title>
    <meta name="description" content="Registre e gerencie seus ganhos financeiros com o InvestAi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: #0d0f14;
            color: #e0e0e0;
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* ===== Gradientes de fundo ===== */
        body::before {
            content: '';
            position: fixed;
            top: -200px; left: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(34,197,94,0.15) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -200px; right: -200px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(6,182,212,0.10) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* ===== Navbar ===== */
        .navbar-custom {
            background: rgba(13,15,20,0.85);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo { font-size: 1.4rem; font-weight: 700; text-decoration: none; color: #fff; }
        .logo span { color: #22c55e; }
        .nav-link-custom {
            color: #888; font-size: 0.88rem; font-weight: 600;
            text-decoration: none; transition: color 0.2s;
        }
        .nav-link-custom:hover, .nav-link-custom.active { color: #22c55e; }
        .user-badge {
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.25);
            color: #4ade80;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        /* ===== Container principal ===== */
        .main-container {
            max-width: 960px;
            margin: 0 auto;
            padding: 30px 20px 60px;
            position: relative;
            z-index: 1;
        }

        /* ===== Header da página ===== */
        .page-header {
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .page-header h1 i { color: #22c55e; margin-right: 10px; }
        .page-header p { color: #666; font-size: 0.92rem; margin: 0; }

        /* ===== Card de resumo ===== */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 22px 20px;
            backdrop-filter: blur(10px);
            transition: transform 0.2s, border-color 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-2px);
            border-color: rgba(34,197,94,0.3);
        }
        .summary-card .label {
            font-size: 0.78rem;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .summary-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4ade80;
        }
        .summary-card .value.neutral { color: #e0e0e0; }

        /* ===== Formulário ===== */
        .form-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 18px;
            padding: 28px 26px;
            backdrop-filter: blur(12px);
            margin-bottom: 30px;
        }
        .form-card h2 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-card h2 i { color: #22c55e; }

        .form-control {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: #e0e0e0 !important;
            border-radius: 10px !important;
            padding: 12px 14px !important;
            font-family: 'Outfit', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 3px rgba(34,197,94,0.15) !important;
            background: rgba(34,197,94,0.04) !important;
        }
        .form-control::placeholder { color: #555 !important; }
        .form-label { color: #aaa; font-size: 0.82rem; font-weight: 600; letter-spacing: 0.03em; }
        .form-check-input {
            background-color: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.2);
        }
        .form-check-input:checked {
            background-color: #22c55e;
            border-color: #22c55e;
        }
        .form-check-label { color: #aaa; font-size: 0.88rem; }

        /* ===== Botões ===== */
        .btn-ganho {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none; border-radius: 10px;
            color: #fff; font-weight: 700; font-size: 0.95rem;
            font-family: 'Outfit', sans-serif;
            cursor: pointer; transition: opacity 0.2s, transform 0.15s;
            padding: 12px 28px;
        }
        .btn-ganho:hover { opacity: 0.9; transform: translateY(-1px); color: #fff; }
        .btn-ganho:active { transform: translateY(0); }
        .btn-ganho:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .btn-cancel {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            color: #888;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 12px 24px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-cancel:hover { border-color: rgba(239,68,68,0.4); color: #f87171; }

        /* ===== Alert de feedback ===== */
        .ganho-alert {
            border-radius: 12px;
            font-size: 0.88rem;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: none;
            animation: slideDown 0.3s ease;
        }
        .ganho-alert.error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
        .ganho-alert.success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== Tabela / Lista de ganhos ===== */
        .ganhos-list {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 18px;
            backdrop-filter: blur(12px);
            overflow: hidden;
        }
        .ganhos-list-header {
            padding: 20px 26px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ganhos-list-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ganhos-list-header h2 i { color: #22c55e; }
        .badge-count {
            background: rgba(34,197,94,0.15);
            color: #4ade80;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .ganho-item {
            display: flex;
            align-items: center;
            padding: 16px 26px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.2s;
        }
        .ganho-item:hover { background: rgba(255,255,255,0.02); }
        .ganho-item:last-child { border-bottom: none; }

        .ganho-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            background: rgba(34,197,94,0.12);
            display: flex; align-items: center; justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }
        .ganho-icon i { color: #4ade80; font-size: 1.1rem; }

        .ganho-info { flex: 1; min-width: 0; }
        .ganho-info .desc {
            font-weight: 600;
            font-size: 0.95rem;
            color: #e0e0e0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ganho-info .meta {
            font-size: 0.78rem;
            color: #555;
            margin-top: 2px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ganho-info .meta .fixo-badge {
            background: rgba(6,182,212,0.15);
            color: #22d3ee;
            padding: 1px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .ganho-valor {
            font-weight: 700;
            font-size: 1.05rem;
            color: #4ade80;
            margin-right: 16px;
            white-space: nowrap;
        }

        .ganho-actions {
            display: flex;
            gap: 6px;
        }
        .ganho-actions button {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            color: #666;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
        }
        .ganho-actions .btn-edit:hover { border-color: rgba(99,102,241,0.4); color: #818cf8; }
        .ganho-actions .btn-delete:hover { border-color: rgba(239,68,68,0.4); color: #f87171; }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #444;
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 14px; display: block; color: #333; }
        .empty-state p { font-size: 0.92rem; margin: 0; }

        /* ===== Modal de edição ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(6px);
            z-index: 200;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.show { display: flex; }
        .modal-card {
            background: #14161d;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px;
            padding: 30px 28px;
            width: 100%;
            max-width: 460px;
            animation: modalIn 0.25s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-card h2 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-card h2 i { color: #22c55e; }

        /* ===== Modal de confirmação exclusão ===== */
        .confirm-card {
            background: #14161d;
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 18px;
            padding: 30px 28px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: modalIn 0.25s ease;
        }
        .confirm-card .icon-danger {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: rgba(239,68,68,0.12);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }
        .confirm-card .icon-danger i { color: #f87171; font-size: 1.5rem; }
        .confirm-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; }
        .confirm-card p { color: #666; font-size: 0.88rem; margin-bottom: 20px; }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none; border-radius: 10px;
            color: #fff; font-weight: 700; font-size: 0.9rem;
            padding: 11px 24px; cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-danger:hover { opacity: 0.9; }

        /* ===== Responsivo ===== */
        @media (max-width: 600px) {
            .ganho-item { flex-wrap: wrap; gap: 10px; }
            .ganho-valor { order: 3; width: 100%; margin-right: 0; }
            .ganho-actions { order: 4; }
            .summary-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar-custom">
    <div class="container d-flex align-items-center justify-content-between" style="max-width:960px;">
        <a href="dashboard.php" class="logo"><i class="bi bi-graph-up-arrow me-1"></i>Invest<span>Ai</span></a>
        <div class="d-flex align-items-center gap-4">
            <a href="dashboard.php" class="nav-link-custom">Dashboard</a>
            <a href="ganhos.php" class="nav-link-custom active">Ganhos</a>
            <span class="user-badge"><i class="bi bi-person-fill me-1"></i><?= $nome ?></span>
            <a href="logout.php" class="nav-link-custom" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>

<div class="main-container">

    <!-- ===== HEADER ===== -->
    <div class="page-header">
        <h1><i class="bi bi-wallet2"></i>Meus Ganhos</h1>
        <p>Registre e acompanhe suas fontes de renda.</p>
    </div>

    <!-- ===== ALERT ===== -->
    <div id="ganho-alert" class="ganho-alert"></div>

    <!-- ===== CARDS DE RESUMO ===== -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="label"><i class="bi bi-cash-stack me-1"></i>Total do Mês</div>
            <div class="value" id="total-mes">R$ 0,00</div>
        </div>
        <div class="summary-card">
            <div class="label"><i class="bi bi-arrow-repeat me-1"></i>Ganhos Fixos</div>
            <div class="value" id="total-fixos">R$ 0,00</div>
        </div>
        <div class="summary-card">
            <div class="label"><i class="bi bi-list-ol me-1"></i>Registros</div>
            <div class="value neutral" id="total-registros">0</div>
        </div>
    </div>

    <!-- ===== FORM NOVO GANHO ===== -->
    <div class="form-card">
        <h2><i class="bi bi-plus-circle"></i>Registrar Novo Ganho</h2>
        <form id="form-ganho" onsubmit="submitGanho(event)">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">DESCRIÇÃO</label>
                    <input type="text" id="ganho-descricao" class="form-control" placeholder="Ex: Salário, Freelance, Venda..." required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">VALOR (R$)</label>
                    <input type="number" id="ganho-valor" class="form-control" placeholder="0,00" step="0.01" min="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">DATA</label>
                    <input type="date" id="ganho-data" class="form-control" required>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="ganho-fixo">
                    <label class="form-check-label" for="ganho-fixo">Ganho fixo (recorrente)</label>
                </div>
                <button type="submit" class="btn-ganho" id="btn-submit">
                    <i class="bi bi-plus-lg me-1"></i>Registrar
                </button>
            </div>
        </form>
    </div>

    <!-- ===== LISTA DE GANHOS ===== -->
    <div class="ganhos-list">
        <div class="ganhos-list-header">
            <h2><i class="bi bi-journal-text"></i>Histórico de Ganhos</h2>
            <span class="badge-count" id="badge-count">0</span>
        </div>
        <div id="ganhos-container">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>Nenhum ganho registrado ainda. Comece adicionando acima!</p>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL EDIÇÃO ===== -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal-card">
        <h2><i class="bi bi-pencil-square"></i>Editar Ganho</h2>
        <form id="form-edit" onsubmit="submitEdit(event)">
            <input type="hidden" id="edit-id">
            <div class="mb-3">
                <label class="form-label">DESCRIÇÃO</label>
                <input type="text" id="edit-descricao" class="form-control" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">VALOR (R$)</label>
                    <input type="number" id="edit-valor" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="col-6">
                    <label class="form-label">DATA</label>
                    <input type="date" id="edit-data" class="form-control" required>
                </div>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="edit-fixo">
                <label class="form-check-label" for="edit-fixo">Ganho fixo (recorrente)</label>
            </div>
            <div class="d-flex gap-3 justify-content-end">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-edit')">Cancelar</button>
                <button type="submit" class="btn-ganho">
                    <i class="bi bi-check-lg me-1"></i>Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL EXCLUIR ===== -->
<div class="modal-overlay" id="modal-delete">
    <div class="confirm-card">
        <div class="icon-danger"><i class="bi bi-trash3"></i></div>
        <h3>Excluir ganho?</h3>
        <p>Esta ação não pode ser desfeita. O registro será removido permanentemente.</p>
        <input type="hidden" id="delete-id">
        <div class="d-flex gap-3 justify-content-center">
            <button class="btn-cancel" onclick="closeModal('modal-delete')">Cancelar</button>
            <button class="btn-danger" onclick="confirmDelete()">
                <i class="bi bi-trash3 me-1"></i>Excluir
            </button>
        </div>
    </div>
</div>

<!-- ===== SCRIPTS ===== -->
<script src="api/ganhos/create.js"></script>
<script src="api/ganhos/read.js"></script>
<script src="api/ganhos/update.js"></script>
<script src="api/ganhos/delete.js"></script>

<script>
const USUARIO_ID = <?= $usuario_id ?>;

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    // Data padrão = hoje
    document.getElementById('ganho-data').value = new Date().toISOString().split('T')[0];
    carregarGanhos();
});

// ===== ALERT =====
function showAlert(msg, type) {
    const el = document.getElementById('ganho-alert');
    el.textContent = msg;
    el.className = 'ganho-alert ' + type;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

// ===== FORMATAR MOEDA =====
function formatMoney(val) {
    return parseFloat(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// ===== FORMATAR DATA =====
function formatDate(dateStr) {
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

// ===== CARREGAR GANHOS =====
async function carregarGanhos() {
    const res = await listarGanhos(USUARIO_ID);
    const container = document.getElementById('ganhos-container');

    if (res.status !== 'success' || !res.ganhos || res.ganhos.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>Nenhum ganho registrado ainda. Comece adicionando acima!</p>
            </div>`;
        document.getElementById('badge-count').textContent = '0';
        document.getElementById('total-registros').textContent = '0';
        document.getElementById('total-mes').textContent = 'R$ 0,00';
        document.getElementById('total-fixos').textContent = 'R$ 0,00';
        return;
    }

    const ganhos = res.ganhos;

    // Atualizar contadores
    const agora = new Date();
    const mesAtual = agora.getMonth();
    const anoAtual = agora.getFullYear();

    let totalMes = 0;
    let totalFixos = 0;

    ganhos.forEach(g => {
        const dt = new Date(g.data_ganho);
        if (dt.getMonth() === mesAtual && dt.getFullYear() === anoAtual) {
            totalMes += parseFloat(g.valor);
        }
        if (parseInt(g.fixo) === 1) {
            totalFixos += parseFloat(g.valor);
        }
    });

    document.getElementById('total-mes').textContent = formatMoney(totalMes);
    document.getElementById('total-fixos').textContent = formatMoney(totalFixos);
    document.getElementById('total-registros').textContent = ganhos.length;
    document.getElementById('badge-count').textContent = ganhos.length;

    // Renderizar lista
    let html = '';
    ganhos.forEach(g => {
        const fixoBadge = parseInt(g.fixo) === 1
            ? '<span class="fixo-badge">FIXO</span>'
            : '';
        html += `
        <div class="ganho-item">
            <div class="ganho-icon"><i class="bi bi-arrow-down-left"></i></div>
            <div class="ganho-info">
                <div class="desc">${escapeHtml(g.descricao)}</div>
                <div class="meta">
                    <span><i class="bi bi-calendar3 me-1"></i>${formatDate(g.data_ganho)}</span>
                    ${fixoBadge}
                </div>
            </div>
            <div class="ganho-valor">+ ${formatMoney(g.valor)}</div>
            <div class="ganho-actions">
                <button class="btn-edit" title="Editar" onclick="openEdit(${g.id}, '${escapeHtml(g.descricao)}', ${g.valor}, '${g.data_ganho}', ${g.fixo})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-delete" title="Excluir" onclick="openDelete(${g.id})">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

// ===== ESCAPE HTML =====
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== SUBMIT NOVO GANHO =====
async function submitGanho(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Salvando...';

    const descricao = document.getElementById('ganho-descricao').value;
    const valor = document.getElementById('ganho-valor').value;
    const data = document.getElementById('ganho-data').value;
    const fixo = document.getElementById('ganho-fixo').checked;

    const res = await criarGanho(descricao, valor, data, fixo, USUARIO_ID);

    if (res.status === 'success') {
        showAlert('✅ Ganho registrado com sucesso!', 'success');
        document.getElementById('form-ganho').reset();
        document.getElementById('ganho-data').value = new Date().toISOString().split('T')[0];
        carregarGanhos();
    } else {
        showAlert('❌ ' + res.message, 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Registrar';
}

// ===== MODAL EDIÇÃO =====
function openEdit(id, descricao, valor, data, fixo) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-descricao').value = descricao;
    document.getElementById('edit-valor').value = valor;
    document.getElementById('edit-data').value = data;
    document.getElementById('edit-fixo').checked = parseInt(fixo) === 1;
    document.getElementById('modal-edit').classList.add('show');
}

async function submitEdit(e) {
    e.preventDefault();
    const id = document.getElementById('edit-id').value;
    const descricao = document.getElementById('edit-descricao').value;
    const valor = document.getElementById('edit-valor').value;
    const data = document.getElementById('edit-data').value;
    const fixo = document.getElementById('edit-fixo').checked;

    const res = await atualizarGanho(id, descricao, valor, data, fixo);

    if (res.status === 'success') {
        showAlert('✅ Ganho atualizado!', 'success');
        closeModal('modal-edit');
        carregarGanhos();
    } else {
        showAlert('❌ ' + res.message, 'error');
    }
}

// ===== MODAL EXCLUSÃO =====
function openDelete(id) {
    document.getElementById('delete-id').value = id;
    document.getElementById('modal-delete').classList.add('show');
}

async function confirmDelete() {
    const id = document.getElementById('delete-id').value;
    const res = await excluirGanho(id);

    if (res.status === 'success') {
        showAlert('✅ Ganho excluído!', 'success');
        closeModal('modal-delete');
        carregarGanhos();
    } else {
        showAlert('❌ ' + res.message, 'error');
    }
}

// ===== FECHAR MODAIS =====
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// Fechar modal clicando fora
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('show');
    });
});
</script>

</body>
</html>
