CREATE DATABASE investia;
USE investia;

-- =========================
-- 1. USUÁRIOS
-- =========================
CREATE TABLE usuarios (
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
CREATE TABLE perfil_financeiro (
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
-- 3. GANHOS
-- =========================
CREATE TABLE ganhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_ganho DATE NOT NULL,
    fixo BOOLEAN NOT NULL DEFAULT FALSE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ganho_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- =========================
-- 4. DESPESAS
-- =========================
CREATE TABLE despesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_despesa DATE NOT NULL,
    fixo BOOLEAN NOT NULL DEFAULT FALSE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_despesa_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- =========================
-- 5. INVESTIMENTOS
-- =========================
CREATE TABLE investimentos (
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
-- 6. PATRIMÔNIOS
-- =========================
CREATE TABLE patrimonios (
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
-- 7. METAS FINANCEIRAS
-- =========================
CREATE TABLE metas_financeiras (
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
-- 8. RESUMOS MENSAIS
-- =========================
CREATE TABLE resumos_mensais (
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
-- 9. NOTÍCIAS FINANCEIRAS
-- =========================
CREATE TABLE noticias_financeiras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    fonte VARCHAR(150) NOT NULL,
    url VARCHAR(500) NOT NULL UNIQUE,
    resumo TEXT,
    categoria VARCHAR(100),
    data_publicacao DATETIME,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 10. SUGESTÕES DE ECONOMIA
-- =========================
CREATE TABLE sugestoes_economia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    fonte TEXT NOT NULL,
    prioridade ENUM('baixa', 'media', 'alta') NOT NULL,
    status ENUM('pendente', 'aceita', 'recusada') DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    respondida_em DATETIME NULL,
    CONSTRAINT fk_sugestao_economia_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- =========================
-- 11. SUGESTÕES DE INVESTIMENTO
-- =========================
CREATE TABLE sugestoes_investimento (
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