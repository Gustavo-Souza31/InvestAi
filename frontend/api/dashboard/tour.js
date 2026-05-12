document.addEventListener('DOMContentLoaded', () => {

    if (!window.driver || !window.driver.js || !window.driver.js.driver) {
        console.error("Driver.js não carregado corretamente.");
        return;
    }

    const { driver } = window.driver.js;

    const driverObj = driver({
        showProgress: true,
        opacity: 0.55,
        nextBtnText: 'Próximo →',
        prevBtnText: '← Voltar',
        doneBtnText: 'Concluir',
        progressText: 'Passo {{current}} de {{total}}',
        steps: [
            {
                popover: {
                    title: 'Bem-vindo ao InvestAi! 🎉',
                    description: 'Ficamos muito felizes em ter você conosco. Preparamos um tour rápido para te apresentar suas novas ferramentas.',
                    side: 'over',
                    align: 'center'
                }
            },
            {
                element: '.summary-cards',
                popover: {
                    title: 'Seu Painel Financeiro 💳',
                    description: 'Logo de cara, o dashboard exibe o seu saldo atualizado em tempo real e consolida o acumulado de ganhos e gastos no mês ativo.',
                    side: 'bottom',
                    align: 'center'
                }
            },
            {
                element: '.nav-resumo',
                popover: {
                    title: 'Analise e Cresça 📊',
                    description: 'Nesta aba, estão os gráficos para uma visão analítica do seu dinheiro. Acesse para visualizar em detalhes!',
                    side: 'bottom',
                    align: 'start'
                }
            },
            {
                element: '.nav-ganhos',
                popover: {
                    title: 'Controle seus Ganhos 📈',
                    description: 'Acesse esta aba para adicionar seu salário, rendimentos ou qualquer entrada de dinheiro. Cadastre receitas fixas ou eventuais.',
                    side: 'bottom',
                    align: 'start'
                }
            },
            {
                element: '.nav-despesas',
                popover: {
                    title: 'Suas Despesas 📉',
                    description: 'Aqui você cadastra e acompanha os gastos do mês. Controle tudo de perto para evitar surpresas e fechar no azul!',
                    side: 'bottom',
                    align: 'start'
                }
            },
            {
                element: '.nav-noticias',
                popover: {
                    title: 'Notícias & IA 📰',
                    description: 'Fique por dentro do mercado financeiro. Nossa IA filtra e categoriza notícias de G1 Economia, InfoMoney e Investing.com, cruzando com seus gastos reais.',
                    side: 'bottom',
                    align: 'start'
                }
            },
            {
                element: '.user-badge',
                popover: {
                    title: 'Seu Perfil 👤',
                    description: 'Acesse suas informações pessoais, altere sua senha e acompanhe seus dados cadastrais a qualquer momento.',
                    side: 'bottom',
                    align: 'end'
                }
            },
            {
                element: '#orcamento-section',
                popover: {
                    title: 'Planejamento de Orçamento 🎯',
                    description: 'Defina limites mensais por categoria e veja em tempo real quanto já gastou. Uma barra de progresso mostra quando você está chegando no limite.',
                    side: 'top',
                    align: 'center'
                }
            },
            {
                element: '#metas-section',
                popover: {
                    title: 'Metas Financeiras 🏁',
                    description: 'Aqui você define objetivos de poupança! Crie metas como "viagem", "novo carro" ou "fundo de emergência" e registre aportes conforme economiza. Veja o progresso em tempo real!',
                    side: 'top',
                    align: 'center'
                }
            },
            {
                element: '#sugestoes-container',
                popover: {
                    title: 'Sugestões da IA 💡',
                    description: 'Com base nos seus gastos, a IA gera sugestões personalizadas de economia todo mês. Clique em qualquer sugestão para atualizar a análise.',
                    side: 'top',
                    align: 'center'
                }
            },
            {
                element: '#chat-fab',
                popover: {
                    title: 'Chat com IA 💬',
                    description: 'No final da página, clique neste botão para abrir o assistente de IA. Tire suas dúvidas sobre finanças, metas, gastos e receba estratégias personalizadas. Faça perguntas livremente!',
                    side: 'top',
                    align: 'center'
                }
            },
            {
                popover: {
                    title: 'Tudo pronto! 🚀',
                    description: 'Você está no controle agora. Para começar bem, que tal adicionar o seu primeiro ganho no menu no topo da página?',
                    side: 'over',
                    align: 'center'
                }
            }
        ]
    });

    driverObj.drive();
});
