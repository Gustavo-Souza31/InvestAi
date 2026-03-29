<?php
// backend/includes/auth_middleware.php — Verifica se o usuário está logado
session_start();

function requireAuth() {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Não autorizado. Faça login primeiro."
        ]);
        exit;
    }
    return $_SESSION['usuario_id'];
}
?>
