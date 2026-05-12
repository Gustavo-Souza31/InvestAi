// ===== FORÇA SENHA =====

function configurarForcaSenha() {
    const input = document.getElementById('perfil-nova-senha');
    const barras = document.querySelectorAll('.strength-bar');
    const label = document.getElementById('strength-label');

    input.addEventListener('input', () => {
        const valor = input.value;
        let forca = 0;

        if (valor.length >= 6) forca++;
        if (valor.length >= 10) forca++;
        if (/[A-Z]/.test(valor) && /[a-z]/.test(valor)) forca++;
        if (/\d/.test(valor)) forca++;
        if (/[^a-zA-Z0-9]/.test(valor)) forca++;

        barras.forEach(barra => { barra.className = 'strength-bar'; });
        label.className = 'strength-label';
        label.textContent = '';

        if (valor.length === 0) return;

        if (forca <= 2) {
            barras[0].classList.add('weak');
            label.classList.add('weak');
            label.textContent = 'Fraca';
        } else if (forca <= 3) {
            barras[0].classList.add('medium');
            barras[1].classList.add('medium');
            label.classList.add('medium');
            label.textContent = 'Média';
        } else {
            barras[0].classList.add('strong');
            barras[1].classList.add('strong');
            barras[2].classList.add('strong');
            label.classList.add('strong');
            label.textContent = 'Forte';
        }

        marcarAlterado();
    });
}

// ===== RASTREAMENTO DE ALTERAÇÕES =====

function marcarAlterado() {
    temAlteracoes = true;
    document.getElementById('btn-save').disabled = false;
    document.getElementById('btn-discard').style.opacity = '1';
}

function configurarEventListeners() {
    document.querySelectorAll('#content input:not([disabled]), #content select').forEach(input => {
        input.addEventListener('input', marcarAlterado);
        input.addEventListener('change', marcarAlterado);
    });

    // Botões de comportamento — só podem ser alterados pelo quiz
    document.querySelectorAll('.behavior-pill').forEach(botao => {
        botao.addEventListener('click', () => {
            if (botao.classList.contains('active')) return;
            abrirQuizComMensagem();
        });
    });

    document.getElementById('btn-save').addEventListener('click', salvarPerfil);

    document.getElementById('btn-discard').addEventListener('click', () => {
        if (dadosPerfil) {
            renderizarPerfil(dadosPerfil);
            document.getElementById('perfil-senha-atual').value = '';
            document.getElementById('perfil-nova-senha').value = '';
            document.getElementById('perfil-confirma-senha').value = '';
            document.querySelectorAll('.strength-bar').forEach(barra => barra.className = 'strength-bar');
            document.getElementById('strength-label').textContent = '';
            temAlteracoes = false;
            document.getElementById('btn-save').disabled = true;
            document.getElementById('btn-discard').style.opacity = '0.5';
            showAlert('Alterações descartadas.', 'error');
        }
    });
}

// ===== MASCARAS E ENTRADA =====

function configurarMascaraTelefone() {
    const input = document.getElementById('perfil-telefone');
    input.addEventListener('input', (evento) => {
        let valor = evento.target.value.replace(/\D/g, '');
        if (valor.length > 11) valor = valor.slice(0, 11);
        if (valor.length >= 7) {
            evento.target.value = valor.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        } else if (valor.length >= 3) {
            evento.target.value = valor.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        } else {
            evento.target.value = valor;
        }
        marcarAlterado();
    });
}

function obterComportamentoSelecionado() {
    const ativo = document.querySelector('.behavior-pill.active');
    return ativo ? ativo.dataset.value : 'moderado';
}

// ===== VALIDAÇÃO =====

function validarDadosPerfil(dados) {
    if (dados.nome === '' || dados.nome.length < 3) {
        return 'O nome deve ter pelo menos 3 caracteres.';
    }

    if (dados.email === '' || dados.email.indexOf('@') === -1 || dados.email.indexOf('.') === -1) {
        return 'E-mail inválido.';
    }

    if (dados.telefone === '' || dados.telefone.length < 14) {
        return 'Telefone inválido. Verifique o DDD e o número.';
    }

    if (dados.novaSenha !== '') {
        if (dados.novaSenha.length < 6) {
            return 'A nova senha deve ter no mínimo 6 caracteres.';
        }
        if (dados.novaSenha !== dados.confirmaSenha) {
            return 'As senhas não coincidem.';
        }
        if (dados.senhaAtual === '') {
            return 'Informe a senha atual para alterar a senha.';
        }
    }

    return null;
}

// ===== SALVAR PERFIL =====

async function salvarPerfil() {
    const botao = document.getElementById('btn-save');
    const textoOriginal = botao.innerHTML;

    const valores = {
        nome: document.getElementById('perfil-nome').value,
        email: document.getElementById('perfil-email').value,
        telefone: document.getElementById('perfil-telefone').value,
        renda_mensal: document.getElementById('perfil-renda').value || 0,
        objetivo_financeiro: document.getElementById('perfil-objetivo').value,
        perfil_comportamento: obterComportamentoSelecionado(),
        senhaAtual: document.getElementById('perfil-senha-atual').value,
        novaSenha: document.getElementById('perfil-nova-senha').value,
        confirmaSenha: document.getElementById('perfil-confirma-senha').value
    };

    const erroValidacao = validarDadosPerfil(valores);
    if (erroValidacao) return showAlert(erroValidacao, 'error');

    const formData = new FormData();
    formData.append('nome', valores.nome);
    formData.append('email', valores.email);
    formData.append('telefone', valores.telefone);
    formData.append('renda_mensal', valores.renda_mensal);
    formData.append('objetivo_financeiro', valores.objetivo_financeiro);
    formData.append('perfil_comportamento', valores.perfil_comportamento);

    if (valores.novaSenha !== '') {
        formData.append('senha_atual', valores.senhaAtual);
        formData.append('nova_senha', valores.novaSenha);
    }

    botao.disabled = true;
    botao.innerHTML = '<div class="loading-spinner" style="width:18px;height:18px;border-width:2px;margin:0;"></div>Salvando...';

    try {
        const resposta = await fetch(`${API_BASE}/update.php`, {
            method: 'POST',
            body: formData
        });
        const resultado = await resposta.json();

        if (resultado.status === 'success') {
            showAlert(resultado.message, 'success');

            if (resultado.nome) {
                const badge = document.querySelector('.user-badge');
                if (badge) badge.innerHTML = `<i class="bi bi-person-fill me-1"></i>${escapeHtml(resultado.nome)}`;
                document.getElementById('profile-name').textContent = resultado.nome;

                let nomesAtualizados = resultado.nome.split(' ');
                let novasIniciais = nomesAtualizados[0][0];
                if (nomesAtualizados.length > 1) {
                    novasIniciais += nomesAtualizados[1][0];
                }
                document.getElementById('avatar-initials').textContent = novasIniciais.toUpperCase();
            }

            document.getElementById('perfil-senha-atual').value = '';
            document.getElementById('perfil-nova-senha').value = '';
            document.getElementById('perfil-confirma-senha').value = '';

            document.querySelectorAll('.strength-bar').forEach(barra => barra.className = 'strength-bar');
            document.getElementById('strength-label').textContent = '';

            temAlteracoes = false;
            document.getElementById('btn-discard').style.opacity = '0.5';

            carregarPerfil();
        } else {
            showAlert(resultado.message || 'Erro ao salvar perfil.', 'error');
        }
    } catch (error) {
        console.error('Erro ao salvar perfil:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    } finally {
        botao.disabled = false;
        botao.innerHTML = textoOriginal;
    }
}
