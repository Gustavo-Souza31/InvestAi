// ===== CARREGAR PERFIL =====

async function carregarPerfil() {
    try {
        const resposta = await fetch(`${API_BASE}/read.php`);
        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao carregar perfil.', 'error');
            return;
        }

        dadosPerfil = resultado;
        renderizarPerfil(resultado);

        document.getElementById('loading').style.display = 'none';
        document.getElementById('content').style.display = 'block';
    } catch (error) {
        console.error('Erro ao carregar perfil:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// ===== RENDERIZAR PERFIL =====

function renderizarPerfil(dados) {
    const usuario = dados.usuario;
    const perfilFinanceiro = dados.perfil_financeiro;
    const estatisticas = dados.estatisticas;

    let palavrasNome = usuario.nome.split(' ');
    let iniciais = palavrasNome[0][0];
    if (palavrasNome.length > 1) {
        iniciais += palavrasNome[1][0];
    }
    document.getElementById('avatar-initials').textContent = iniciais.toUpperCase();

    document.getElementById('profile-name').textContent = usuario.nome;
    document.getElementById('profile-email-display').textContent = usuario.email;

    const dataCriacao = new Date(usuario.criado_em);
    const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    document.getElementById('member-since').textContent = `Membro desde ${meses[dataCriacao.getMonth()]} ${dataCriacao.getFullYear()}`;

    document.getElementById('stat-ganhos').textContent = estatisticas.count_ganhos;
    document.getElementById('stat-despesas').textContent = estatisticas.count_despesas;
    document.getElementById('stat-saldo').textContent = formatMoney(estatisticas.total_ganhos - estatisticas.total_despesas);

    document.getElementById('perfil-nome').value = usuario.nome;
    document.getElementById('perfil-email').value = usuario.email;
    document.getElementById('perfil-cpf').value = formatarCPF(usuario.cpf);
    document.getElementById('perfil-telefone').value = formatarTelefone(usuario.telefone);

    if (perfilFinanceiro) {
        document.getElementById('perfil-renda').value = perfilFinanceiro.renda_mensal || '';
        document.getElementById('perfil-objetivo').value = perfilFinanceiro.objetivo_financeiro || '';
        selecionarComportamento(perfilFinanceiro.perfil_comportamento || 'moderado');
    } else {
        selecionarComportamento('moderado');
    }
}

// ===== FORMATAÇÃO =====

function formatarCPF(cpf) {
    if (!cpf) return '';
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11) return cpf;
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function formatarTelefone(telefone) {
    if (!telefone) return '';
    telefone = telefone.replace(/\D/g, '');
    if (telefone.length === 11) {
        return telefone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (telefone.length === 10) {
        return telefone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    return telefone;
}

// ===== COMPORTAMENTO =====

function selecionarComportamento(tipo) {
    document.querySelectorAll('.behavior-pill').forEach(botao => {
        botao.classList.toggle('active', botao.dataset.value === tipo);
    });
}
