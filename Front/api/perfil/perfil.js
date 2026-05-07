const API_BASE = '../backend/api/perfil';
let dadosPerfil = null;
let temAlteracoes = false;

// Listener DOM para inicializar
document.addEventListener('DOMContentLoaded', () => {
    carregarPerfil();
    configurarEventListeners();
    configurarSesoes();
    configurarForcaSenha();
    configurarMascaraTelefone();
});

// ===== CARREGAR PERFIL =====

async function carregarPerfil() {

    try {
        // Busca dados do backend
        const resposta = await fetch(`${API_BASE}/read.php`);
        const dados = await resposta.json();

        // Se erro, mostra mensagem
        if (dados.status !== 'success') {
            showAlert(dados.message || 'Erro ao carregar perfil.', 'error');
            return;
        }

        // Salva dados e renderiza
        dadosPerfil = dados;
        renderizarPerfil(dados);

        // Esconde loading e mostra conteúdo
        document.getElementById('loading').style.display = 'none';
        document.getElementById('content').style.display = 'block';
    } catch (erro) {
        // Erro de conexão
        console.error('Erro ao carregar perfil:', erro);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}

// ===== RENDERIZAR PERFIL =====

function renderizarPerfil(dados) {

    const usuario = dados.usuario;
    const perfilFinanceiro = dados.perfil_financeiro;
    const estatisticas = dados.estatisticas;

    // Gera iniciais do avatar
    let palavrasNome = usuario.nome.split(' ');
    let iniciais = palavrasNome[0][0];
    if (palavrasNome.length > 1) {
        iniciais += palavrasNome[1][0];
    }
    document.getElementById('avatar-initials').textContent = iniciais.toUpperCase();

    // Preenche dados do header
    document.getElementById('profile-name').textContent = usuario.nome;
    document.getElementById('profile-email-display').textContent = usuario.email;

    // Data de membro
    const dataCriacao = new Date(usuario.criado_em);
    const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    document.getElementById('member-since').textContent = `Membro desde ${meses[dataCriacao.getMonth()]} ${dataCriacao.getFullYear()}`;

    // Preenche estatísticas
    document.getElementById('stat-ganhos').textContent = estatisticas.count_ganhos;
    document.getElementById('stat-despesas').textContent = estatisticas.count_despesas;
    document.getElementById('stat-saldo').textContent = formatMoney(estatisticas.total_ganhos - estatisticas.total_despesas);

    // Preenche formulário - dados pessoais
    document.getElementById('perfil-nome').value = usuario.nome;
    document.getElementById('perfil-email').value = usuario.email;
    document.getElementById('perfil-cpf').value = formatarCPF(usuario.cpf);
    document.getElementById('perfil-telefone').value = formatarTelefone(usuario.telefone);

    // Preenche formulário - perfil financeiro
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

// ===== COMPORTAMENTO =====

function selecionarComportamento(tipo) {
    document.querySelectorAll('.behavior-pill').forEach(botao => {
        botao.classList.toggle('active', botao.dataset.value === tipo);
    });
}

function obterComportamentoSelecionado() {
    const ativo = document.querySelector('.behavior-pill.active');
    return ativo ? ativo.dataset.value : 'moderado';
}

// ===== SEÇÕES RETRÁTEIS =====

function configurarSesoes() {
    document.querySelectorAll('.section-header').forEach(cabecalho => {
        cabecalho.addEventListener('click', () => {
            const corpo = cabecalho.nextElementSibling;
            const estaRetraido = corpo.classList.contains('collapsed');

            if (estaRetraido) {
                corpo.classList.remove('collapsed');
                cabecalho.classList.remove('collapsed');
            } else {
                corpo.classList.add('collapsed');
                cabecalho.classList.add('collapsed');
            }
        });
    });
}

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

        // Limpa
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

    // Rastreia mudanças em inputs
    document.querySelectorAll('#content input:not([disabled]), #content select').forEach(input => {
        input.addEventListener('input', marcarAlterado);
        input.addEventListener('change', marcarAlterado);
    });

    // Botões de comportamento — só podem ser alterados pelo quiz
    document.querySelectorAll('.behavior-pill').forEach(botao => {
        botao.addEventListener('click', () => {
            // Se já está ativo, não faz nada
            if (botao.classList.contains('active')) return;

            // Se tentou mudar manualmente, abre o quiz
            abrirQuizComMensagem();
        });
    });

    // Botão salvar
    document.getElementById('btn-save').addEventListener('click', salvarPerfil);

    // Botão descartar
    document.getElementById('btn-discard').addEventListener('click', () => {
        if (dadosPerfil) {
            renderizarPerfil(dadosPerfil);
            document.getElementById('perfil-senha-atual').value = '';
            document.getElementById('perfil-nova-senha').value = '';
            document.getElementById('perfil-confirma-senha').value = '';
            // Limpa barra de força
            document.querySelectorAll('.strength-bar').forEach(barra => barra.className = 'strength-bar');
            document.getElementById('strength-label').textContent = '';
            temAlteracoes = false;
            document.getElementById('btn-save').disabled = true;
            document.getElementById('btn-discard').style.opacity = '0.5';
            showAlert('Alterações descartadas.', 'error');
        }
    });
}

// ===== VALIDAÇÃO =====

function validarDadosPerfil(dados) {

    // Valida nome
    if (dados.nome === '' || dados.nome.length < 3) {
        return 'O nome deve ter pelo menos 3 caracteres.';
    }

    // Valida email
    if (dados.email === '' || dados.email.indexOf('@') === -1 || dados.email.indexOf('.') === -1) {
        return 'E-mail inválido.';
    }

    // Valida telefone
    if (dados.telefone === '' || dados.telefone.length < 14) {
        return 'Telefone inválido. Verifique o DDD e o número.';
    }
    
    // Valida senha (se preenchido)
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

    // Coleta valores do formulário
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

    // Valida dados
    const erroValidacao = validarDadosPerfil(valores);
    if (erroValidacao) return showAlert(erroValidacao, 'error');

    // Prepara FormData
    const formData = new FormData();
    formData.append('nome', valores.nome);
    formData.append('email', valores.email);
    formData.append('telefone', valores.telefone);
    formData.append('renda_mensal', valores.renda_mensal);
    formData.append('objetivo_financeiro', valores.objetivo_financeiro);
    formData.append('perfil_comportamento', valores.perfil_comportamento);

    // Se nova senha, envia senhas
    if (valores.novaSenha !== '') {
        formData.append('senha_atual', valores.senhaAtual);
        formData.append('nova_senha', valores.novaSenha);
    }

    // Loading state
    botao.disabled = true;
    botao.innerHTML = '<div class="loading-spinner" style="width:18px;height:18px;border-width:2px;margin:0;"></div>Salvando...';

    try {
        // Envia para backend
        const resposta = await fetch(`${API_BASE}/update.php`, { 
            method: 'POST', 
            body: formData 
        });
        
        const dados = await resposta.json();

        // Se sucesso, atualiza UI e recarrega
        if (dados.status === 'success') {
            showAlert(dados.message, 'success');
            
            if (dados.nome) {
                const badge = document.querySelector('.user-badge');
                if (badge) badge.innerHTML = `<i class="bi bi-person-fill me-1"></i>${escapeHtml(dados.nome)}`;
                document.getElementById('profile-name').textContent = dados.nome;
                
                // Atualiza iniciais
                let nomesAtualizados = dados.nome.split(' ');
                let novasIniciais = nomesAtualizados[0][0];
                if (nomesAtualizados.length > 1) {
                    novasIniciais += nomesAtualizados[1][0];
                }
                document.getElementById('avatar-initials').textContent = novasIniciais.toUpperCase();
            }

            // Limpa campos de senha
            document.getElementById('perfil-senha-atual').value = '';
            document.getElementById('perfil-nova-senha').value = '';
            document.getElementById('perfil-confirma-senha').value = '';
            
            document.querySelectorAll('.strength-bar').forEach(barra => barra.className = 'strength-bar');
            document.getElementById('strength-label').textContent = '';

            temAlteracoes = false;
            document.getElementById('btn-discard').style.opacity = '0.5';

            // Recarrega dados completos
            carregarPerfil();
        } else {
            // Se erro, mostra mensagem
            showAlert(dados.message || 'Erro ao salvar perfil.', 'error');
        }
    } catch (erro) {
        // Erro de conexão
        console.error('Erro ao salvar perfil:', erro);
        showAlert('Erro de conexão com o servidor.', 'error');
    } finally {
        // Restaura botão
        botao.disabled = false;
        botao.innerHTML = textoOriginal;
    }
}

// ============================================================
//   QUIZ DE PERFIL DE INVESTIDOR
// ============================================================

const QUIZ_PERGUNTAS = [
    {
        texto: '💰 Você recebeu R$ 1.000 inesperados. O que você faz?',
        opcoes: [
            { letra: 'A', texto: 'Guardo tudo ou coloco em uma aplicação segura.',         perfil: 'conservador' },
            { letra: 'B', texto: 'Guardo metade e uso o restante para algo que quero.',    perfil: 'moderado' },
            { letra: 'C', texto: 'Comemoro! Compro algo que estava querendo há tempos.',   perfil: 'gastador' },
        ]
    },
    {
        texto: '📊 Como você se sente ao ver seu saldo no final do mês?',
        opcoes: [
            { letra: 'A', texto: 'Satisfeito quando guardei mais do que gastei.',          perfil: 'conservador' },
            { letra: 'B', texto: 'Tranquilo, desde que as contas estejam pagas.',          perfil: 'moderado' },
            { letra: 'C', texto: 'Surpreso com o quanto gastei, mas não me arrependo.',    perfil: 'gastador' },
        ]
    },
    {
        texto: '💳 Qual é a sua relação com dívidas e parcelamentos?',
        opcoes: [
            { letra: 'A', texto: 'Evito a todo custo — só compro o que posso pagar à vista.', perfil: 'conservador' },
            { letra: 'B', texto: 'Parcelo quando vale a pena, mas controlo os limites.',       perfil: 'moderado' },
            { letra: 'C', texto: 'Tenho várias parcelas — facilita comprar o que quero agora.', perfil: 'gastador' },
        ]
    },
    {
        texto: '🛒 Antes de fazer uma compra grande, você...',
        opcoes: [
            { letra: 'A', texto: 'Pesquiso por semanas, comparo preços e espero a oferta ideal.', perfil: 'conservador' },
            { letra: 'B', texto: 'Pesquiso um pouco, mas decido relativamente rápido.',           perfil: 'moderado' },
            { letra: 'C', texto: 'Compro na hora — se quero e tenho dinheiro, por que esperar?',  perfil: 'gastador' },
        ]
    },
    {
        texto: '🛡️ Sua reserva de emergência é...',
        opcoes: [
            { letra: 'A', texto: 'Tenho mais de 6 meses de gastos guardados.',             perfil: 'conservador' },
            { letra: 'B', texto: 'Tenho entre 1 e 3 meses de gastos reservados.',          perfil: 'moderado' },
            { letra: 'C', texto: 'Não tenho reserva — o dinheiro acaba antes de sobrar.',  perfil: 'gastador' },
        ]
    },
    {
        texto: '📈 Investir em ações ou criptomoedas te deixa...',
        opcoes: [
            { letra: 'A', texto: 'Com medo — prefiro Tesouro Direto ou poupança.',                 perfil: 'conservador' },
            { letra: 'B', texto: 'Interessado, mas cauteloso — coloco só uma pequena parte.',       perfil: 'moderado' },
            { letra: 'C', texto: 'Animado! Gosto do risco e da possibilidade de lucro rápido.',     perfil: 'gastador' },
        ]
    },
    {
        texto: '📅 Você planeja ou anota seus gastos mensais?',
        opcoes: [
            { letra: 'A', texto: 'Sim! Tenho planilha ou app e sigo rigorosamente.',           perfil: 'conservador' },
            { letra: 'B', texto: 'Faço um controle básico, mas não tão rígido.',               perfil: 'moderado' },
            { letra: 'C', texto: 'Não costumo planejar — prefiro viver o dia a dia.',          perfil: 'gastador' },
        ]
    },
    {
        texto: '🎯 Para você, dinheiro é...',
        opcoes: [
            { letra: 'A', texto: 'Segurança e proteção para o futuro.',                perfil: 'conservador' },
            { letra: 'B', texto: 'Uma ferramenta para equilibrar conforto e futuro.',  perfil: 'moderado' },
            { letra: 'C', texto: 'Feito para ser curtido e vivido no presente.',       perfil: 'gastador' },
        ]
    },
];

const QUIZ_RESULTADOS = {
    conservador: {
        icone: '🛡️',
        titulo: 'Perfil Conservador',
        desc: 'Você prioriza segurança e estabilidade acima de tudo. Prefere garantias a riscos e constrói patrimônio com consistência e disciplina. Seu futuro financeiro está em boas mãos!',
        classe: 'resultado-conservador',
    },
    moderado: {
        icone: '⚖️',
        titulo: 'Perfil Moderado',
        desc: 'Você equilibra bem gastos e poupança. Aceita algum risco desde que haja planejamento. É o perfil mais versátil: aproveita o presente sem comprometer o futuro.',
        classe: 'resultado-moderado',
    },
    gastador: {
        icone: '🔥',
        titulo: 'Perfil Gastador',
        desc: 'Você vive intensamente o presente! Com pequenos ajustes de hábito — como uma reserva de emergência — pode ter o melhor dos dois mundos: prazer hoje e segurança amanhã.',
        classe: 'resultado-gastador',
    },
};

let quizIndiceAtual = 0;
let quizRespostas = { conservador: 0, moderado: 0, gastador: 0 };
let quizResultadoFinal = null;

function abrirQuiz() {
    reiniciarQuiz();
    document.getElementById('quiz-overlay').classList.add('active');
}

function abrirQuizComMensagem() {
    reiniciarQuiz();
    // Exibe uma dica contextual no topo do quiz
    const label = document.getElementById('quiz-progress-label');
    label.textContent = '💡 Faça o quiz para definir seu perfil!';
    label.style.color = '#a78bfa';
    // Restaura após 3s
    setTimeout(() => {
        label.style.color = '';
        atualizarProgresso();
    }, 3000);
    document.getElementById('quiz-overlay').classList.add('active');
}

function fecharQuiz() {
    document.getElementById('quiz-overlay').classList.remove('active');
}

function reiniciarQuiz() {
    quizIndiceAtual = 0;
    quizRespostas = { conservador: 0, moderado: 0, gastador: 0 };
    quizResultadoFinal = null;
    document.getElementById('quiz-resultado').style.display = 'none';
    document.getElementById('quiz-body').style.display = 'block';
    atualizarProgresso();
    renderizarPergunta();
}

function atualizarProgresso() {
    const total = QUIZ_PERGUNTAS.length;
    const pct   = (quizIndiceAtual / total) * 100;
    document.getElementById('quiz-progress-bar').style.width = pct + '%';
    document.getElementById('quiz-progress-label').textContent =
        quizIndiceAtual < total
            ? `Pergunta ${quizIndiceAtual + 1} de ${total}`
            : `Quiz concluído!`;
}

function renderizarPergunta() {
    const pergunta = QUIZ_PERGUNTAS[quizIndiceAtual];
    const letras   = ['A', 'B', 'C'];

    const html = `
        <div class="quiz-pergunta">
            <h4>${pergunta.texto}</h4>
            <div class="quiz-opcoes">
                ${pergunta.opcoes.map((op, i) => `
                    <button class="quiz-opcao" onclick="responder('${op.perfil}')">
                        <span class="opcao-letra">${letras[i]}</span>
                        <span>${op.texto}</span>
                    </button>
                `).join('')}
            </div>
        </div>
    `;
    document.getElementById('quiz-body').innerHTML = html;
}

function responder(perfil) {
    quizRespostas[perfil]++;
    quizIndiceAtual++;
    atualizarProgresso();

    if (quizIndiceAtual < QUIZ_PERGUNTAS.length) {
        renderizarPergunta();
    } else {
        mostrarResultado();
    }
}

function mostrarResultado() {
    // Determina o vencedor
    let max = -1;
    let vencedor = 'moderado';
    for (const [perfil, votos] of Object.entries(quizRespostas)) {
        if (votos > max) { max = votos; vencedor = perfil; }
    }
    quizResultadoFinal = vencedor;

    const r = QUIZ_RESULTADOS[vencedor];
    const divResultado = document.getElementById('quiz-resultado');
    divResultado.className = `quiz-resultado ${r.classe}`;
    document.getElementById('resultado-icon').textContent   = r.icone;
    document.getElementById('resultado-titulo').textContent = r.titulo;
    document.getElementById('resultado-desc').textContent   = r.desc;

    document.getElementById('quiz-body').style.display    = 'none';
    divResultado.style.display = 'block';
}

function aplicarResultadoEFechar() {
    if (!quizResultadoFinal) return;
    // Aplica o perfil nas pills
    selecionarComportamento(quizResultadoFinal);
    fecharQuiz();
    // Salva automaticamente no banco de dados
    salvarPerfil();
}

// Fechar ao clicar fora do modal
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('quiz-overlay')?.addEventListener('click', (e) => {
        if (e.target.id === 'quiz-overlay') fecharQuiz();
    });
});
