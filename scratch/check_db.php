<?php
require_once '/Applications/MAMP/htdocs/InvestAi/DataBase/conexao.php';
$res = $conexao->query("DESCRIBE noticias_ai");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
