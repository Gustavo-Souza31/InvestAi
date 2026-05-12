const API_BASE = BASE_PATH + '/backend/api/perfil';
let dadosPerfil = null;
let temAlteracoes = false;

// ===== INICIALIZAÇÃO =====

document.addEventListener('DOMContentLoaded', () => {
    carregarPerfil();
    configurarEventListeners();
    configurarSesoes();
    configurarForcaSenha();
    configurarMascaraTelefone();
});

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

// ===== QUIZ =====

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
    const label = document.getElementById('quiz-progress-label');
    label.textContent = '💡 Faça o quiz para definir seu perfil!';
    label.style.color = '#a78bfa';
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
    selecionarComportamento(quizResultadoFinal);
    fecharQuiz();
    // Salva automaticamente o resultado do quiz
    salvarPerfil();
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('quiz-overlay')?.addEventListener('click', (e) => {
        if (e.target.id === 'quiz-overlay') fecharQuiz();
    });
});
