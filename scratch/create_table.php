<?php
require_once '/Applications/MAMP/htdocs/InvestAi/DataBase/conexao.php';
$sql = "CREATE TABLE IF NOT EXISTS noticias_ai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    noticia_hash VARCHAR(32) NOT NULL,
    resposta_ia TEXT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_news (usuario_id, noticia_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conexao->query($sql)) {
    echo "Tabela noticias_ai criada com sucesso!";
} else {
    echo "Erro ao criar tabela: " . $conexao->error;
}
?>
