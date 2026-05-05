CREATE DATABASE IF NOT EXISTS investai;
USE investai;

-- =========================
-- 1. USUÁRIOS
-- =========================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    cpf CHAR(11) NOT NULL UNIQUE,
    telefone VARCHAR(20) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================
-- 2. PERFIL FINANCEIRO
-- =========================
CREATE TABLE IF NOT EXISTS perfil_financeiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    renda_mensal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo_inicial DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    possui_investimentos BOOLEAN NOT NULL DEFAULT FALSE,
    possui_patrimonio BOOLEAN NOT NULL DEFAULT FALSE,
    objetivo_financeiro VARCHAR(255),
    perfil_comportamento ENUM('conservador', 'moderado', 'gastador') DEFAULT 'moderado',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_financeiro_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- =========================
-- 3. CATEGORIAS (GANHOS E DESPESAS)
-- DEVE ser criada ANTES de ganhos e despesas (FK referenciada por ambas)
-- =========================
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('ganho', 'despesa') NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- =========================
-- 4. GANHOS
-- =========================
CREATE TABLE IF NOT EXISTS ganhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_ganho DATE NOT NULL,
    fixo BOOLEAN NOT NULL DEFAULT FALSE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    categoria_id INT NULL,
    CONSTRAINT fk_ganho_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ganho_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE SET NULL
);

-- =========================
-- 5. DESPESAS
-- =========================
CREATE TABLE IF NOT EXISTS despesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_despesa DATE NOT NULL,
    fixo BOOLEAN NOT NULL DEFAULT FALSE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    categoria_id INT NULL,
    CONSTRAINT fk_despesa_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_despesa_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE SET NULL
);

-- =========================
-- 6. INVESTIMENTOS
-- =========================
CREATE TABLE IF NOT EXISTS investimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_investimento VARCHAR(100) NOT NULL,
    nome_ativo VARCHAR(150) NOT NULL,
    valor_aplicado DECIMAL(12,2) NOT NULL,
    valor_atual DECIMAL(12,2),
    risco ENUM('baixo', 'medio', 'alto') DEFAULT 'medio',
    data_aplicacao DATE,
    observacao TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_investimento_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- =========================
-- 7. PATRIMÔNIOS
-- =========================
CREATE TABLE IF NOT EXISTS patrimonios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_bem VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor_estimado DECIMAL(12,2) NOT NULL,
    data_aquisicao DATE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patrimonio_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- =========================
-- 8. METAS FINANCEIRAS
-- =========================
CREATE TABLE IF NOT EXISTS metas_financeiras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT,
    valor_meta DECIMAL(12,2) NOT NULL,
    valor_atual DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    data_objetivo DATE,
    status ENUM('em_andamento', 'concluida', 'cancelada') DEFAULT 'em_andamento',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_meta_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- =========================
-- 9. RESUMOS MENSAIS
-- =========================
CREATE TABLE IF NOT EXISTS resumos_mensais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ano YEAR NOT NULL,
    mes TINYINT NOT NULL,
    total_ganhos DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_despesas DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo_inicial_mes DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo_final_mes DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    economia_mes DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resumo_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT uq_resumo_usuario_mes UNIQUE (usuario_id, ano, mes),
    CONSTRAINT ck_mes_valido CHECK (mes BETWEEN 1 AND 12)
);

-- =========================
-- 10. NOTÍCIAS FINANCEIRAS
-- (Inclui colunas de análise IA adicionadas pelo ai_news_processor.php)
-- =========================
CREATE TABLE IF NOT EXISTS noticias_financeiras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    fonte VARCHAR(150) NOT NULL,
    url VARCHAR(500) NOT NULL UNIQUE,
    resumo TEXT,
    categoria VARCHAR(100),
    nivel_impacto ENUM('baixo', 'medio', 'alto') DEFAULT 'baixo',
    cenario_hipotetico TEXT NULL,
    acoes_praticas TEXT NULL,
    sugestao_investimento TEXT NULL,
    dica_economia TEXT NULL,
    cor_fonte VARCHAR(20) DEFAULT '#6366f1',
    icone_fonte VARCHAR(50) DEFAULT 'bi-newspaper',
    processado_ia TINYINT(1) NOT NULL DEFAULT 0,
    data_publicacao DATETIME,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================
-- 11. SUGESTÕES DE ECONOMIA
-- =========================
CREATE TABLE IF NOT EXISTS sugestoes_economia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    fonte TEXT NOT NULL,
    categoria_nome VARCHAR(100) DEFAULT NULL,
    mes TINYINT DEFAULT NULL,
    ano YEAR DEFAULT NULL,
    prioridade ENUM('baixa', 'media', 'alta') NOT NULL,
    status ENUM('pendente', 'aceita', 'recusada') DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    respondida_em DATETIME NULL,
    CONSTRAINT fk_sugestao_economia_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    UNIQUE INDEX uq_sugestao_user_cat_mes (usuario_id, categoria_nome, mes, ano)
);

-- =========================
-- 12. SUGESTÕES DE INVESTIMENTO
-- =========================
CREATE TABLE IF NOT EXISTS sugestoes_investimento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    noticia_id INT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    fonte TEXT NOT NULL,
    risco ENUM('baixo', 'medio', 'alto') NOT NULL,
    status ENUM('pendente', 'aceita', 'recusada') DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    respondida_em DATETIME NULL,
    CONSTRAINT fk_sugestao_investimento_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sugestao_investimento_noticia
        FOREIGN KEY (noticia_id) REFERENCES noticias_financeiras(id)
        ON DELETE SET NULL
);

-- =========================
-- 13. CACHE IA NOTÍCIAS
-- (Usada pelo analyze.php para evitar chamar a IA repetidamente)
-- =========================
CREATE TABLE IF NOT EXISTS cache_ia_noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    noticia_url_hash CHAR(32) NOT NULL,
    perfil_usuario VARCHAR(50) NOT NULL DEFAULT 'moderado',
    categorias_usuario TEXT NULL,
    analise_json LONGTEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hash_perfil (noticia_url_hash, perfil_usuario)
);

-- =========================
-- 14. ORÇAMENTO CATEGORIAS
-- (Limites de gastos mensais por categoria)
-- =========================
CREATE TABLE IF NOT EXISTS orcamento_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_nome VARCHAR(100) NOT NULL,
    limite_mensal DECIMAL(12,2) NOT NULL,
    mes TINYINT NOT NULL DEFAULT (MONTH(CURDATE())),
    ano YEAR NOT NULL DEFAULT (YEAR(CURDATE())),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orcamento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT uq_orcamento UNIQUE (usuario_id, categoria_nome, mes, ano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
