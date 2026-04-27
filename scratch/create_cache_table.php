<?php
require_once 'DataBase/conexao.php';

$sql = "CREATE TABLE IF NOT EXISTS cache_ia_noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    noticia_url_hash CHAR(32) NOT NULL,
    perfil_usuario VARCHAR(50) NOT NULL,
    categorias_usuario TEXT NOT NULL,
    analise_json JSON NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (noticia_url_hash),
    INDEX (perfil_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conexao->query($sql)) {
    echo "Tabela cache_ia_noticias criada com sucesso.";
} else {
    echo "Erro ao criar tabela: " . $conexao->error;
}
