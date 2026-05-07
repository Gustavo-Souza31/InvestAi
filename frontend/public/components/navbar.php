<?php
// $nav_active deve ser definido antes do include:
// 'dashboard' | 'resumo' | 'ganhos' | 'despesas' | 'noticias' | 'perfil'
$nav = isset($nav_active) ? $nav_active : '';
$nome_nav = isset($nome) ? $nome : htmlspecialchars($_SESSION['usuario_nome'] ?? '');
?>
<!-- ===== NAVBAR ===== -->
<nav class="navbar-custom">
    <div class="container d-flex align-items-center justify-content-between" style="max-width:1200px;">
        <a href="dashboard.php" class="logo">
            <svg class="neural-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 18L9 13M9 13L15 15M15 15L20 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="4" cy="18" r="2" fill="currentColor"/>
                <circle cx="9" cy="13" r="2" fill="currentColor"/>
                <circle cx="15" cy="15" r="2" fill="currentColor"/>
                <circle cx="20" cy="6" r="3" fill="var(--brand-accent)"/>
            </svg>
            Invest<span>AI</span>
        </a>
        <div class="d-flex align-items-center gap-4">
            <a href="dashboard.php" class="nav-link-custom <?= $nav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="resumo.php"    class="nav-link-custom nav-resumo   <?= $nav === 'resumo'    ? 'active' : '' ?>">Resumo Financeiro</a>
            <a href="ganhos.php"    class="nav-link-custom nav-ganhos   <?= $nav === 'ganhos'    ? 'active' : '' ?>">Ganhos</a>
            <a href="despesas.php"  class="nav-link-custom nav-despesas <?= $nav === 'despesas'  ? 'active' : '' ?>">Despesas</a>
            <a href="noticias.php"  class="nav-link-custom nav-noticias <?= $nav === 'noticias'  ? 'active' : '' ?>">Notícias IA</a>
            <a href="perfil.php" class="user-badge <?= $nav === 'perfil' ? 'active' : '' ?>">
                <i class="bi bi-person-fill me-1"></i><?= $nome_nav ?>
            </a>
            <a href="../../../logout.php" class="nav-link-custom" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>
