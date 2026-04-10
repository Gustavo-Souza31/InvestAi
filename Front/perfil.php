<?php
session_start();

// Redirecionar se não logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$nome = htmlspecialchars($_SESSION['usuario_nome']);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestAi — Meu Perfil</title>
    <meta name="description" content="Gerencie seu perfil pessoal e financeiro no InvestAi.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style/css/variables.css">
    <link rel="stylesheet" href="assets/style/css/animations.css">
    <link rel="stylesheet" href="assets/style/css/navbar.css">
    <link rel="stylesheet" href="assets/style/css/internal-pages.css">
    <link rel="stylesheet" href="assets/style/css/perfil.css?v=<?= time() ?>">
</head>

<body>

    <div class="main-container">

        <!-- ===== HEADER ===== -->
        <div class="page-header">
            <h1><i class="bi bi-person-gear"></i>Meu Perfil</h1>
            <p>Gerencie suas informações pessoais e preferências financeiras.</p>
        </div>

        <!-- ===== ALERT ===== -->
        <div id="perfil-alert" class="alert-message"></div>

        <!-- ===== LOADING STATE ===== -->
        <div id="loading" class="loading">
            <div class="loading-spinner"></div>
            <p class="text-secondary">Carregando perfil...</p>
        </div>

        <!-- ===== CONTENT ===== -->
        <div id="content" style="display: none;">

            <!-- ===== PROFILE HEADER ===== -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <span id="avatar-initials">--</span>
                    <div class="status-dot"></div>
                </div>
                <div class="profile-info">
                    <h2 id="profile-name">—</h2>
                    <p class="member-since">
                        <i class="bi bi-calendar3"></i>
                        <span id="member-since">—</span>
                    </p>
                    <p class="member-since" style="margin-top: -4px;">
                        <i class="bi bi-envelope"></i>
                        <span id="profile-email-display">—</span>
                    </p>
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <i class="bi bi-arrow-up-right" style="color:#4ade80;"></i>
                            <span class="stat-value" id="stat-ganhos">0</span> ganhos
                        </div>
                        <div class="profile-stat">
                            <i class="bi bi-arrow-down-left" style="color:#f87171;"></i>
                            <span class="stat-value" id="stat-despesas">0</span> despesas
                        </div>
                        <div class="profile-stat">
                            <i class="bi bi-wallet2" style="color:#818cf8;"></i>
                            <span class="stat-value" id="stat-saldo">R$ 0,00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SECTIONS ===== -->
            <div class="profile-sections">

                <!-- ===== DADOS PESSOAIS ===== -->
                <div class="profile-section">
                    <div class="section-header">
                        <div class="section-icon personal"><i class="bi bi-person"></i></div>
                        <div style="flex:1;">
                            <h3>Dados Pessoais</h3>
                            <p class="section-subtitle">Nome, e-mail e telefone</p>
                        </div>
                        <i class="bi bi-chevron-down toggle-icon"></i>
                    </div>
                    <div class="section-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="perfil-nome">NOME COMPLETO</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-person"></i>
                                    <input type="text" id="perfil-nome" class="form-control" placeholder="Seu nome completo">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="perfil-email">E-MAIL</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-envelope"></i>
                                    <input type="email" id="perfil-email" class="form-control" placeholder="seu@email.com">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="perfil-cpf">CPF</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-fingerprint"></i>
                                    <input type="text" id="perfil-cpf" class="form-control" disabled placeholder="000.000.000-00">
                                </div>
                                <span class="field-hint"><i class="bi bi-lock-fill"></i> O CPF não pode ser alterado.</span>
                            </div>
                            <div class="form-group">
                                <label for="perfil-telefone">TELEFONE</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-phone"></i>
                                    <input type="text" id="perfil-telefone" class="form-control" placeholder="(00) 00000-0000" maxlength="15">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== PERFIL FINANCEIRO ===== -->
                <div class="profile-section">
                    <div class="section-header">
                        <div class="section-icon financial"><i class="bi bi-cash-coin"></i></div>
                        <div style="flex:1;">
                            <h3>Perfil Financeiro</h3>
                            <p class="section-subtitle">Renda, metas e comportamento</p>
                        </div>
                        <i class="bi bi-chevron-down toggle-icon"></i>
                    </div>
                    <div class="section-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="perfil-renda">RENDA MENSAL (R$)</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                    <input type="number" id="perfil-renda" class="form-control" placeholder="0,00" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="perfil-objetivo">OBJETIVO FINANCEIRO</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-bullseye"></i>
                                    <input type="text" id="perfil-objetivo" class="form-control" placeholder="Ex: Comprar um carro, viajar...">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 12px;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3" style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.2); padding: 16px 20px; border-radius: 16px; margin-bottom: 24px;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="background: rgba(99, 102, 241, 0.15); width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #818cf8;">
                                        <i class="bi bi-patch-question-fill"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0 0 2px 0; color: #e0e7ff;">Descubra o seu Perfil</h4>
                                        <p style="font-size: 0.8rem; margin: 0; color: #a5b4fc;">Não tem certeza? Faça um quiz rápido e nós ajudamos.</p>
                                    </div>
                                </div>
                                <button type="button" onclick="showToast('Em breve! O Quiz de Perfil de Investidor será adicionado nas próximas atualizações. Fique ligado!', 'success')" style="background: linear-gradient(135deg, #6366f1, #4f46e5); border: none; color: white; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    Fazer Quiz
                                </button>
                            </div>

                            <label>SELECIONE SEU PERFIL DE COMPORTAMENTO</label>
                            <div class="behavior-pills">
                                <div class="behavior-pill conservador" data-value="conservador">
                                    <span class="pill-icon">🛡️</span>
                                    <span class="pill-label">Conservador</span>
                                    <span class="pill-desc">Prioriza economizar</span>
                                </div>
                                <div class="behavior-pill moderado" data-value="moderado">
                                    <span class="pill-icon">⚖️</span>
                                    <span class="pill-label">Moderado</span>
                                    <span class="pill-desc">Equilíbrio entre gastar e poupar</span>
                                </div>
                                <div class="behavior-pill gastador" data-value="gastador">
                                    <span class="pill-icon">🔥</span>
                                    <span class="pill-label">Gastador</span>
                                    <span class="pill-desc">Tende a gastar mais</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== SEGURANÇA ===== -->
                <div class="profile-section">
                    <div class="section-header">
                        <div class="section-icon security"><i class="bi bi-shield-lock"></i></div>
                        <div style="flex:1;">
                            <h3>Segurança</h3>
                            <p class="section-subtitle">Altere sua senha de acesso</p>
                        </div>
                        <i class="bi bi-chevron-down toggle-icon"></i>
                    </div>
                    <div class="section-body">
                        <div class="form-row single">
                            <div class="form-group">
                                <label for="perfil-senha-atual">SENHA ATUAL</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" id="perfil-senha-atual" class="form-control" placeholder="••••••••">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="perfil-nova-senha">NOVA SENHA</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-key"></i>
                                    <input type="password" id="perfil-nova-senha" class="form-control" placeholder="Mín. 6 caracteres">
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                </div>
                                <span class="strength-label" id="strength-label"></span>
                            </div>
                            <div class="form-group">
                                <label for="perfil-confirma-senha">CONFIRMAR NOVA SENHA</label>
                                <div class="input-with-icon">
                                    <i class="bi bi-key-fill"></i>
                                    <input type="password" id="perfil-confirma-senha" class="form-control" placeholder="Repita a nova senha">
                                </div>
                            </div>
                        </div>
                        <span class="field-hint"><i class="bi bi-info-circle"></i> Deixe em branco se não deseja alterar a senha.</span>
                    </div>
                </div>

            </div>

            <!-- ===== ACTION BAR ===== -->
            <div class="action-bar">
                <button class="btn-discard" id="btn-discard" style="opacity: 0.5;">
                    <i class="bi bi-x-lg me-1"></i>Descartar
                </button>
                <button class="btn-save" id="btn-save" disabled>
                    <i class="bi bi-check2-all me-1"></i>Salvar Alterações
                </button>
            </div>

        </div>

    </div>

    <!-- ===== SCRIPTS ===== -->
    <script src="api/utils/shared.js"></script>
    <script src="api/utils/nav.js"></script>
    <script src="assets/style/js/ui.js"></script>
    <script src="api/perfil/perfil.js?v=<?= time() ?>"></script>

</body>

</html>
