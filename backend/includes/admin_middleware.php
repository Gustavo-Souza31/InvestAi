<?php
// backend/includes/admin_middleware.php — Verifica se o usuário é administrador

function requireAdmin(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Não autorizado.']);
        exit;
    }

    if (empty($_SESSION['is_admin'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Acesso restrito.']);
        exit;
    }
}

function requireAdminPage(): void
{
    if (empty($_SESSION['is_admin'])) {
        // Sem sessão nenhuma → login; com sessão mas não admin → dashboard
        if (empty($_SESSION['usuario_id']) && empty($_SESSION['usuario_email'])) {
            header('Location: ../../../login.php');
            exit;
        }
        header('Location: ../user/dashboard.php');
        exit;
    }
}
?>
