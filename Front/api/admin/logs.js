// Front/api/admin/logs.js — Busca e renderiza logs no painel admin

const LogsAdmin = (() => {
    let estadoAtual = { pagina: 1, nivel: '', acao: '', usuario_id: 0, de: '', ate: '' };

    function init() {
        document.getElementById('btn-filtrar-logs').addEventListener('click', () => {
            estadoAtual.pagina  = 1;
            estadoAtual.nivel       = document.getElementById('filtro-nivel').value;
            estadoAtual.acao        = document.getElementById('filtro-acao').value.trim();
            estadoAtual.usuario_id  = parseInt(document.getElementById('filtro-uid').value) || 0;
            estadoAtual.de          = document.getElementById('filtro-de').value;
            estadoAtual.ate         = document.getElementById('filtro-ate').value;
            carregar();
        });

        document.getElementById('btn-limpar-logs').addEventListener('click', () => {
            document.getElementById('filtro-nivel').value   = '';
            document.getElementById('filtro-acao').value    = '';
            document.getElementById('filtro-uid').value     = '';
            document.getElementById('filtro-de').value      = '';
            document.getElementById('filtro-ate').value     = '';
            estadoAtual = { pagina: 1, nivel: '', acao: '', usuario_id: 0, de: '', ate: '' };
            carregar();
        });

        carregar();
    }

    async function carregar() {
        const tbody   = document.getElementById('logs-tbody');
        const info    = document.getElementById('logs-info');
        const paginacao = document.getElementById('logs-paginacao');

        tbody.innerHTML = '<tr><td colspan="8" class="admin-loading"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</td></tr>';

        const params = new URLSearchParams({
            pagina:     estadoAtual.pagina,
            nivel:      estadoAtual.nivel,
            acao:       estadoAtual.acao,
            usuario_id: estadoAtual.usuario_id,
            de:         estadoAtual.de,
            ate:        estadoAtual.ate,
        });

        try {
            const res  = await fetch(`../backend/api/admin/logs.php?${params}`);
            const data = await res.json();

            if (data.status !== 'success') {
                tbody.innerHTML = `<tr><td colspan="8" class="admin-empty"><i class="bi bi-exclamation-circle"></i>Erro ao carregar logs.</td></tr>`;
                return;
            }

            renderLogs(data.logs, tbody);
            renderPaginacao(data, paginacao);
            info.textContent = `${data.total} registro(s) encontrado(s)`;
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="8" class="admin-empty"><i class="bi bi-wifi-off"></i>Falha na requisição.</td></tr>`;
        }
    }

    function renderLogs(logs, tbody) {
        if (!logs.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="admin-empty"><i class="bi bi-journal-x"></i>Nenhum log encontrado.</td></tr>';
            return;
        }

        tbody.innerHTML = logs.map(log => {
            const detalhesStr = log.detalhes ? JSON.stringify(log.detalhes) : '—';
            const detalhesAbrev = detalhesStr.length > 50 ? detalhesStr.slice(0, 50) + '…' : detalhesStr;
            const ts = log.timestamp ? log.timestamp.replace('T', ' ') : '—';

            return `<tr>
                <td style="font-size:0.82rem;white-space:nowrap;color:#c9cdd4;font-variant-numeric:tabular-nums">${ts}</td>
                <td><span class="badge-nivel badge-${log.nivel}">${log.nivel}</span></td>
                <td>${escHtml(log.usuario_email || '—')}</td>
                <td class="text-muted" style="font-size:0.78rem">${escHtml(log.ip || '—')}</td>
                <td><code style="font-size:0.8rem">${escHtml(log.acao)}</code></td>
                <td>
                    ${log.detalhes
                        ? `<span class="log-detalhes" title="Clique para expandir" data-detalhes="${escHtml(JSON.stringify(log.detalhes, null, 2))}" onclick="LogsAdmin.verDetalhes(this)">${escHtml(detalhesAbrev)}</span>`
                        : '<span class="text-muted">—</span>'}
                </td>
                <td><span class="badge-nivel badge-${log.status === 'sucesso' ? 'INFO' : 'ERROR'}">${log.status}</span></td>
            </tr>`;
        }).join('');
    }

    function renderPaginacao(data, container) {
        const { pagina, total_paginas, total, por_pagina } = data;
        const ini = ((pagina - 1) * por_pagina) + 1;
        const fim = Math.min(pagina * por_pagina, total);

        container.innerHTML = `
            <div class="admin-pagination">
                <span>Exibindo ${ini}–${fim} de ${total}</span>
                <div class="page-btns">
                    <button ${pagina <= 1 ? 'disabled' : ''} onclick="LogsAdmin.irPara(${pagina - 1})">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <span style="padding:0.3rem 0.5rem;font-size:0.82rem">Pág. ${pagina} / ${total_paginas}</span>
                    <button ${pagina >= total_paginas ? 'disabled' : ''} onclick="LogsAdmin.irPara(${pagina + 1})">
                        Próxima <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>`;
    }

    function irPara(pag) {
        estadoAtual.pagina = pag;
        carregar();
    }

    function verDetalhes(el) {
        const raw = el.getAttribute('data-detalhes');
        try {
            const obj = JSON.parse(raw);
            document.getElementById('modal-log-body').textContent = JSON.stringify(obj, null, 2);
        } catch {
            document.getElementById('modal-log-body').textContent = raw;
        }
        new bootstrap.Modal(document.getElementById('modal-log-detalhes')).show();
    }

    function escHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Expõe funções necessárias para chamadas inline no HTML
    return { init, irPara, verDetalhes };
})();
