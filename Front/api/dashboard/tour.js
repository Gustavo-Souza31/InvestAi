document.addEventListener('DOMContentLoaded', () => {

    if (!window.driver || !window.driver.js || !window.driver.js.driver) {
        console.error("Driver.js não carregado corretamente.");
        return;
    }

    const { driver } = window.driver.js;

    const driverObj = driver({
        showProgress: true,
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
                    title: 'Dashboard 💳',
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
                popover: {
                    title: 'Tudo pronto! 🚀',
                    description: 'Você está no controle agora. Para começar bem, que tal adicionar o seu primeiro ganho no menu no topo da página?',
                    side: 'over',
                    align: 'center'
                }
            }
        ]
    });

    // Inicia o Tour!
    driverObj.drive();
});
