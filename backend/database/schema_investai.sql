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
    ativo TINYINT(1) NOT NULL DEFAULT 1,
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
-- Deve ser criada ANTES de ganhos e despesas (FK referenciada por ambas)
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
    categoria_id INT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    categoria_id INT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_despesa_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_despesa_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE SET NULL
);

-- =========================
-- 6. NOTÍCIAS FINANCEIRAS
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
-- 7. CACHE IA NOTÍCIAS
-- (Usada pelo analyze.php para evitar chamar a IA repetidamente)
-- =========================
CREATE TABLE IF NOT EXISTS cache_ia_noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    noticia_url_hash CHAR(32) NOT NULL,
    perfil_usuario VARCHAR(50) NOT NULL DEFAULT 'moderado',
    analise_json LONGTEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hash_perfil (noticia_url_hash, perfil_usuario)
);

-- =========================
-- 8. CACHE EXPLICAÇÕES IA (explain.php)
-- =========================
CREATE TABLE IF NOT EXISTS noticias_ai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    noticia_hash CHAR(32) NOT NULL,
    resposta_ia LONGTEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario_hash (usuario_id, noticia_hash),
    CONSTRAINT fk_noticias_ai_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- =========================
-- 9. SUGESTÕES DE ECONOMIA
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
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sugestao_economia_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    UNIQUE INDEX uq_sugestao_user_cat_mes (usuario_id, categoria_nome, mes, ano)
);

-- =========================
-- 10. ORÇAMENTO POR CATEGORIA
-- =========================
CREATE TABLE IF NOT EXISTS orcamento_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    limite_mensal DECIMAL(12,2) NOT NULL,
    mes TINYINT NOT NULL DEFAULT (MONTH(CURDATE())),
    ano YEAR NOT NULL DEFAULT (YEAR(CURDATE())),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orcamento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_orcamento_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    CONSTRAINT uq_orcamento UNIQUE (usuario_id, categoria_id, mes, ano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 11. METAS FINANCEIRAS
-- Usuário cria metas com prazo e valor; aportes registram depósitos
-- =========================
CREATE TABLE IF NOT EXISTS metas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    valor_total DECIMAL(12,2) NOT NULL,
    valor_guardado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    prazo DATE NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_meta_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 12. APORTES (depósitos para metas)
-- Não são despesas, mas devem ser refletidos no Resumo Financeiro como saídas identificadas
-- =========================
CREATE TABLE IF NOT EXISTS aportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    meta_id INT NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_aporte DATE NOT NULL DEFAULT (CURDATE()),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_aporte_meta FOREIGN KEY (meta_id) REFERENCES metas(id) ON DELETE CASCADE,
    CONSTRAINT fk_aporte_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 13. LOGS DE AUDITORIA
-- =========================
CREATE TABLE IF NOT EXISTS logs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    timestamp     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nivel         ENUM('INFO','WARN','ERROR','DEBUG') NOT NULL,
    usuario_id    INT          NULL,
    usuario_email VARCHAR(150) NULL,
    ip            VARCHAR(45)  NOT NULL DEFAULT '0.0.0.0',
    acao          VARCHAR(100) NOT NULL,
    detalhes      JSON         NULL,
    status        ENUM('sucesso','falha') NOT NULL,
    INDEX idx_nivel     (nivel),
    INDEX idx_acao      (acao),
    INDEX idx_usuario   (usuario_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
