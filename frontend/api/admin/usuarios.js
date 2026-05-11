// Front/api/admin/usuarios.js — Busca e renderiza usuários no painel admin

const UsuariosAdmin = (() => {
    let estadoAtual = { pagina: 1, busca: '' };

    function init() {
        document.getElementById('btn-buscar-usuarios').addEventListener('click', () => {
            estadoAtual.pagina = 1;
            estadoAtual.busca  = document.getElementById('busca-usuario').value.trim();
            carregar();
        });

        document.getElementById('busca-usuario').addEventListener('keydown', e => {
            if (e.key === 'Enter') document.getElementById('btn-buscar-usuarios').click();
        });

        carregar();
    }

    async function carregar() {
        const tbody   = document.getElementById('usuarios-tbody');
        const info    = document.getElementById('usuarios-info');
        const paginacao = document.getElementById('usuarios-paginacao');

        tbody.innerHTML = '<tr><td colspan="6" class="admin-loading"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</td></tr>';

        const params = new URLSearchParams({
            pagina: estadoAtual.pagina,
            busca:  estadoAtual.busca,
        });

        try {
            const res  = await fetch(`${BASE_PATH}/backend/api/admin/usuarios.php?${params}`);
            const data = await res.json();

            if (data.status !== 'success') {
                tbody.innerHTML = `<tr><td colspan="6" class="admin-empty"><i class="bi bi-exclamation-circle"></i>Erro ao carregar usuários.</td></tr>`;
                return;
            }

            renderUsuarios(data.usuarios, tbody);
            renderPaginacao(data, paginacao);
            info.textContent = `${data.total} usuário(s) encontrado(s)`;
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" class="admin-empty"><i class="bi bi-wifi-off"></i>Falha na requisição.</td></tr>`;
        }
    }

    function renderUsuarios(usuarios, tbody) {
        if (!usuarios.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="admin-empty"><i class="bi bi-people"></i>Nenhum usuário encontrado.</td></tr>';
            return;
        }

        tbody.innerHTML = usuarios.map(u => {
            const statusClass = u.ativo ? 'ativo' : 'inativo';
            const statusLabel = u.ativo ? 'Ativo' : 'Inativo';
            const btnLabel    = u.ativo ? 'Desativar' : 'Ativar';
            const btnClass    = u.ativo ? 'btn-outline-danger' : 'btn-outline-success';
            const dataCad     = u.criado_em ? u.criado_em.split(' ')[0] : '—';
            const renda       = u.renda_mensal ? 'R$ ' + parseFloat(u.renda_mensal).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '—';
            const perfil      = u.perfil_comportamento || '—';

            return `<tr id="row-usuario-${u.id}">
                <td><strong>${escHtml(u.nome)}</strong></td>
                <td class="text-muted">${escHtml(u.email)}</td>
                <td class="text-muted" style="font-size:0.82rem">${dataCad}</td>
                <td><small class="text-muted">${escHtml(perfil)}</small></td>
                <td><span class="user-status-badge ${statusClass}">${statusLabel}</span></td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm ${btnClass}"
                            onclick="UsuariosAdmin.toggle(${u.id}, ${u.ativo ? 0 : 1}, this)">
                            ${btnLabel}
                        </button>
                        <button class="btn btn-sm btn-outline-secondary"
                            onclick="UsuariosAdmin.verLogs(${u.id})">
                            <i class="bi bi-journal-text"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    async function toggle(usuarioId, novoAtivo, btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res  = await fetch(BASE_PATH + '/backend/api/admin/toggle_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: usuarioId, ativo: novoAtivo }),
            });
            const data = await res.json();

            if (data.status === 'success') {
                carregar();
            } else {
                alert(data.message || 'Erro ao alterar usuário.');
                btn.disabled = false;
                btn.textContent = novoAtivo ? 'Ativar' : 'Desativar';
            }
        } catch {
            alert('Falha na requisição.');
            btn.disabled = false;
        }
    }

    function verLogs(uid) {
        // Troca para aba de logs e aplica filtro pelo usuário
        document.querySelector('[data-tab="logs"]').click();
        document.getElementById('filtro-uid').value = uid;
        document.getElementById('btn-filtrar-logs').click();
    }

    function renderPaginacao(data, container) {
        const { pagina, total_paginas, total, por_pagina } = data;
        const ini = ((pagina - 1) * por_pagina) + 1;
        const fim = Math.min(pagina * por_pagina, total);

        container.innerHTML = `
            <div class="admin-pagination">
                <span>Exibindo ${ini}–${fim} de ${total}</span>
                <div class="page-btns">
                    <button ${pagina <= 1 ? 'disabled' : ''} onclick="UsuariosAdmin.irPara(${pagina - 1})">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <span style="padding:0.3rem 0.5rem;font-size:0.82rem">Pág. ${pagina} / ${total_paginas}</span>
                    <button ${pagina >= total_paginas ? 'disabled' : ''} onclick="UsuariosAdmin.irPara(${pagina + 1})">
                        Próxima <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>`;
    }

    function irPara(pag) {
        estadoAtual.pagina = pag;
        carregar();
    }

    function escHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return { init, toggle, verLogs, irPara };
})();
