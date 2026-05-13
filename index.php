<?php
// Redirecionar para a pasta frontend onde está a aplicação principal
// Detecta o caminho dinamicamente
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
header('Location: ' . $basePath . '/frontend/');
exit;
