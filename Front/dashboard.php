<?php
session_start();
// Redireciona para login se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login.php');
    exit;
}
$nome = htmlspecialchars($_SESSION['usuario_nome']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestAi — Área Logada</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #0d0f14; 
            color: white; 
            font-family: 'Outfit', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .welcome-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 50px;
            border-radius: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        .text-gradient {
            background: linear-gradient(135deg, #6366f1, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>

    <div class="welcome-card shadow-lg">
        <h1 class="display-4 fw-bold mb-3">Login <span class="text-success">Sucesso!</span></h1>
        <p class="lead mb-4">Bem-vindo(a) de volta, <span class="text-gradient fw-bold"><?= $nome ?></span>.</p>
        
        <hr class="opacity-10 my-4">
        
        <div class="d-flex flex-column gap-2">
            <p class="text-secondary small">Você está autenticado de forma segura.</p>
            <a href="/logout.php" class="btn btn-outline-danger rounded-pill px-4 mt-2">Sair da Conta</a>
        </div>
    </div>

</body>
</html>
