USE investia;

-- =========================
-- USUÁRIO
-- =========================
INSERT INTO usuarios (nome, email, cpf, telefone, senha_hash)
VALUES (
    'Rafael Monteiro',
    'rafael.monteiro@exemplo.com',
    '12345678901',
    '41999123456',
    'hash_teste_rafael_123'
);

-- =========================
-- PERFIL FINANCEIRO
-- =========================
INSERT INTO perfil_financeiro (
    usuario_id,
    renda_mensal,
    saldo_inicial,
    possui_investimentos,
    possui_patrimonio,
    objetivo_financeiro,
    perfil_comportamento
) VALUES (
    1,
    4500.00,
    1200.00,
    TRUE,
    TRUE,
    'Economizar mais e investir melhor ao longo do ano',
    'moderado'
);

-- =========================
-- GANHOS
-- =========================
INSERT INTO ganhos (usuario_id, descricao, valor, data_ganho, fixo)
VALUES
(1, 'Salário mensal', 4500.00, '2026-03-05', TRUE),
(1, 'Freelance de site institucional', 400.00, '2026-03-12', FALSE),
(1, 'Ganhei 20 reais no Tigrinho KKK', 20.00, '2026-03-14', FALSE);

-- =========================
-- DESPESAS
-- =========================
INSERT INTO despesas (usuario_id, descricao, valor, data_despesa, fixo)
VALUES
(1, 'Aluguel', 1200.00, '2026-03-01', TRUE),
(1, 'Internet', 99.90, '2026-03-03', TRUE),
(1, 'Lanche da tarde', 5.00, '2026-03-06', FALSE),
(1, 'iFood pizza', 49.90, '2026-03-08', FALSE),
(1, 'Uber para faculdade', 18.00, '2026-03-09', FALSE),
(1, 'Mercado do mês', 210.75, '2026-03-10', FALSE);

-- =========================
-- INVESTIMENTOS
-- =========================
INSERT INTO investimentos (
    usuario_id,
    tipo_investimento,
    nome_ativo,
    valor_aplicado,
    valor_atual,
    risco,
    data_aplicacao,
    observacao
) VALUES (
    1,
    'Criptomoeda',
    'Bitcoin',
    300.00,
    328.50,
    'alto',
    '2026-03-15',
    'Primeiro aporte pequeno em cripto'
);

-- =========================
-- PATRIMÔNIOS
-- =========================
INSERT INTO patrimonios (
    usuario_id,
    tipo_bem,
    descricao,
    valor_estimado,
    data_aquisicao
) VALUES (
    1,
    'Eletrônico',
    'Notebook Lenovo IdeaPad',
    3200.00,
    '2025-07-20'
);

-- =========================
-- METAS FINANCEIRAS
-- =========================
INSERT INTO metas_financeiras (
    usuario_id,
    titulo,
    descricao,
    valor_meta,
    valor_atual,
    data_objetivo,
    status
) VALUES (
    1,
    'Reserva de emergência',
    'Guardar dinheiro para ter segurança financeira',
    6000.00,
    1200.00,
    '2026-12-31',
    'em_andamento'
);

-- =========================
-- RESUMO MENSAL
-- =========================
INSERT INTO resumos_mensais (
    usuario_id,
    ano,
    mes,
    total_ganhos,
    total_despesas,
    saldo_inicial_mes,
    saldo_final_mes,
    economia_mes
) VALUES (
    1,
    2026,
    3,
    4920.00,
    1583.55,
    1200.00,
    4536.45,
    3336.45
);

-- =========================
-- NOTÍCIA FINANCEIRA
-- =========================
INSERT INTO noticias_financeiras (
    titulo,
    fonte,
    url,
    resumo,
    categoria,
    data_publicacao
) VALUES (
    'Bitcoin volta a subir com melhora do mercado global',
    'Portal Finance News',
    'https://portalfinance-news.com/bitcoin-sobe-mercado-global',
    'O ativo apresentou valorização após melhora do sentimento do mercado internacional.',
    'Criptomoedas',
    '2026-03-18 10:30:00'
);

-- =========================
-- SUGESTÃO DE ECONOMIA
-- =========================
INSERT INTO sugestoes_economia (
    usuario_id,
    titulo,
    descricao,
    fonte,
    prioridade,
    status
) VALUES (
    1,
    'Reduzir gastos variáveis com delivery',
    'Você poderia diminuir pedidos por aplicativo nesta semana para economizar mais.',
    'Foram registradas despesas variáveis com lanche e iFood nos últimos dias.',
    'alta',
    'pendente'
);

-- =========================
-- SUGESTÃO DE INVESTIMENTO
-- =========================
INSERT INTO sugestoes_investimento (
    usuario_id,
    noticia_id,
    titulo,
    descricao,
    fonte,
    risco,
    status
) VALUES (
    1,
    1,
    'Avaliar pequeno aporte em Bitcoin',
    'Com base no cenário recente, pode ser interessante considerar um aporte pequeno e controlado.',
    'Sugestão baseada na notícia cadastrada e no interesse do usuário por investimentos.',
    'alto',
    'pendente'
);