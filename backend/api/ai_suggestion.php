<?php
// backend/api/ai_suggestion.php
// Esse arquivo atuará como um endpoint de exemplo para retornar dados via AJAX / Fetch API para o JS
header('Content-Type: application/json');

// Simulação de resposta da IA
$response = [
    "status" => "success",
    "meta" => "Viagem ao Japão",
    "suggestion" => "Baseado no seu perfil, investir R$ 500 no Tesouro Direto hoje pode acelerar sua meta em 2 meses.",
    "monthly_balance" => 12450.00
];

echo json_encode($response);
?>