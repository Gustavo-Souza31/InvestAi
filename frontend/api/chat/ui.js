(function () {
    const STORAGE_KEY  = 'inventai_chat_historico';
    const MAX_HISTORY  = 20; // máx mensagens salvas (10 trocas)
    const MAX_CONTEXTO = 10; // máx mensagens enviadas pra IA como contexto

    let chatAberto = false;
    let enviando   = false;
    let historico  = [];    // [{role:'usuario'|'assistente', texto:'...'}]

    // ── Persistência ──────────────────────────────────────────────────────────

    function carregarHistorico() {
        try {
            const salvo = sessionStorage.getItem(STORAGE_KEY);
            historico = salvo ? JSON.parse(salvo) : [];
        } catch (_) {
            historico = [];
        }
    }

    function salvarHistorico() {
        if (historico.length > MAX_HISTORY) {
            historico = historico.slice(-MAX_HISTORY);
        }
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(historico));
        } catch (_) {}
    }

    // ── Init ──────────────────────────────────────────────────────────────────

    function inicializarChat() {
        if (document.getElementById('chat-fab')) return;
        carregarHistorico();

        // FAB (botão flutuante)
        const fab = document.createElement('button');
        fab.id        = 'chat-fab';
        fab.className = 'chat-fab';
        fab.title     = 'Assistente IA';
        fab.innerHTML = '<i class="bi bi-chat-dots-fill"></i>';
        fab.addEventListener('click', alternarChat);

        // Painel do chat
        const painel = document.createElement('div');
        painel.id        = 'chat-painel';
        painel.className = 'chat-painel';
        painel.innerHTML = `
            <div class="chat-header">
                <div class="chat-header-info">
                    <span class="chat-avatar">🤖</span>
                    <div>
                        <div class="chat-titulo">Assistente InvestAI</div>
                        <div class="chat-subtitulo">IA financeira</div>
                    </div>
                </div>
                <button class="chat-fechar" id="chat-fechar" title="Fechar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="chat-mensagens" id="chat-mensagens"></div>
            <div class="chat-input-area">
                <input
                    type="text"
                    id="chat-input"
                    class="chat-input"
                    placeholder="Ex: crie um orçamento de R$ 500 de roupas"
                    maxlength="500"
                    autocomplete="off"
                />
                <button id="chat-enviar" class="chat-enviar-btn" title="Enviar">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        `;

        document.body.appendChild(fab);
        document.body.appendChild(painel);

        // Eventos
        document.getElementById('chat-fechar').addEventListener('click', fecharChat);
        document.getElementById('chat-enviar').addEventListener('click', onEnviar);
        document.getElementById('chat-input').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                onEnviar();
            }
        });

        // Restaurar mensagens salvas ou mostrar boas-vindas
        if (historico.length > 0) {
            historico.forEach(({ role, texto }) => _renderMensagem(texto, role));
        } else {
            _renderMensagem(
                'Olá! 👋 Posso criar orçamentos, consultar seus gastos e muito mais. Como posso ajudar?',
                'assistente'
            );
        }
    }

    // ── Controles do painel ───────────────────────────────────────────────────

    function alternarChat() {
        chatAberto ? fecharChat() : abrirChat();
    }

    function abrirChat() {
        chatAberto = true;
        document.getElementById('chat-painel').classList.add('aberto');
        document.getElementById('chat-fab').classList.add('ativo');
        setTimeout(() => document.getElementById('chat-input')?.focus(), 200);
    }

    function fecharChat() {
        chatAberto = false;
        document.getElementById('chat-painel').classList.remove('aberto');
        document.getElementById('chat-fab').classList.remove('ativo');
    }

    // ── Mensagens ─────────────────────────────────────────────────────────────

    // Só renderiza na tela (sem salvar no histórico)
    function _renderMensagem(texto, tipo) {
        const container = document.getElementById('chat-mensagens');
        if (!container) return;

        const msg = document.createElement('div');
        msg.className   = `chat-msg chat-msg-${tipo}`;
        msg.textContent = texto;

        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }

    // Renderiza E persiste no histórico
    function adicionarMensagem(texto, tipo) {
        _renderMensagem(texto, tipo);
        historico.push({ role: tipo, texto });
        salvarHistorico();
    }

    function mostrarCarregando() {
        const container = document.getElementById('chat-mensagens');
        if (!container) return;

        const loading = document.createElement('div');
        loading.id        = 'chat-loading';
        loading.className = 'chat-msg chat-msg-assistente chat-loading';
        loading.innerHTML = '<span></span><span></span><span></span>';

        container.appendChild(loading);
        container.scrollTop = container.scrollHeight;
    }

    function ocultarCarregando() {
        document.getElementById('chat-loading')?.remove();
    }

    // ── Envio ─────────────────────────────────────────────────────────────────

    async function onEnviar() {
        console.trace('[CHAT] onEnviar chamado');
        if (enviando) {
            console.log('[CHAT] bloqueado por enviando=true');
            return;
        }

        const input = document.getElementById('chat-input');
        const texto = input.value.trim();

        if (!texto) return;

        enviando = true;
        input.value = '';
        adicionarMensagem(texto, 'usuario');
        document.getElementById('chat-enviar').disabled = true;
        mostrarCarregando();

        // Passa as últimas N mensagens como contexto (excluindo a que acabou de ser adicionada)
        const contexto = historico.slice(-(MAX_CONTEXTO + 1), -1);

        try {
            const resultado = await window.chatAPI.enviarMensagem(texto, null, null, contexto);
            ocultarCarregando();

            if (resultado) {
                adicionarMensagem(resultado.resposta, 'assistente');
                recarregarAposAcao(resultado.acao, resultado.precisa_confirmacao);
            } else {
                adicionarMensagem('Tive um problema. Pode tentar novamente? 😅', 'assistente');
            }
        } catch (err) {
            ocultarCarregando();
            adicionarMensagem('Erro de conexão. Verifique sua internet e tente novamente.', 'assistente');
            console.error('Erro no chat:', err);
        } finally {
            enviando = false;
            document.getElementById('chat-enviar').disabled = false;
            input.focus();
        }
    }

    // ── Reload pós-ação ───────────────────────────────────────────────────────

    function recarregarAposAcao(acao, precisa_confirmacao = false) {
        if (!acao
            || precisa_confirmacao
            || acao === 'conversa'
            || acao === 'pedir_confirmacao'
            || acao === 'consultar_gastos'
            || acao === 'consultar_orcamentos'
            || acao === 'consultar_ganhos'
            || acao === 'consultar_metas'
            || acao === 'consultar_aportes'
            || acao === 'resumo_dashboard'
        ) return;

        const recarregarDashboard = [
            'criar_despesa', 'editar_despesa', 'deletar_despesa', 'deletar_todas_despesas',
            'criar_ganho',   'editar_ganho',   'deletar_ganho',   'deletar_todos_ganhos',
        ];

        const recarregarSoOrcamentos = [
            'criar_orcamento', 'editar_orcamento', 'deletar_orcamento', 'deletar_todos_orcamentos',
        ];

        if (recarregarDashboard.includes(acao)) {
            if (typeof inicializar === 'function') {
                inicializar(window.DEFAULT_PERIODO || '3m', true);
            }
            if (typeof carregarOrcamentos === 'function') carregarOrcamentos();

            const acoesDespesa = ['criar_despesa', 'editar_despesa', 'deletar_despesa', 'deletar_todas_despesas'];
            const acoesGanho   = ['criar_ganho',   'editar_ganho',   'deletar_ganho',   'deletar_todos_ganhos'];

            if (acoesDespesa.includes(acao) && typeof carregarDespesas === 'function') {
                carregarDespesas();
            }
            if (acoesGanho.includes(acao) && typeof carregarGanhos === 'function') {
                carregarGanhos();
            }
        }

        if (recarregarSoOrcamentos.includes(acao)) {
            if (typeof carregarOrcamentos === 'function') carregarOrcamentos();
            if (typeof inicializarSugestoes === 'function') {
                const hoje = new Date();
                inicializarSugestoes(hoje.getMonth() + 1, hoje.getFullYear());
            }
        }

        if (['criar_meta', 'editar_meta', 'deletar_meta', 'deletar_todas_metas', 'criar_aporte', 'editar_aporte', 'deletar_aporte'].includes(acao)) {
            if (typeof carregarMetas === 'function') carregarMetas();
        }
    }

    // ── Limpar histórico no logout ────────────────────────────────────────────

    function registrarLimpezaLogout() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href*="logout"]');
            if (link) sessionStorage.removeItem(STORAGE_KEY);
        });
    }

    // ── Boot ──────────────────────────────────────────────────────────────────

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { inicializarChat(); registrarLimpezaLogout(); });
    } else {
        inicializarChat();
        registrarLimpezaLogout();
    }
})();
