<?php
// Redirecionar para a pasta frontend onde está a aplicação principal
// Detecta o caminho dinamicamente até "inventai" (funciona para qualquer membro da equipe)
$uri = $_SERVER['REQUEST_URI'];
$pos = strpos($uri, '/inventai');
$basePath = ($pos !== false) ? substr($uri, 0, $pos + strlen('/inventai')) : '/inventai';
header('Location: ' . $basePath . '/frontend/');
exit;

