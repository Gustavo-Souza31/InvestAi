<?php
/**
 * AuthValidator.php — Validações para autenticação (com validações reais)
 */

require_once __DIR__ . '/ValidatorHelper.php';

class AuthValidator {

    /**
     * Valida dados de login
     */
    public static function validateLogin($data) {
        $errors = [];

        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';

        // Verificar campos vazios
        if (empty($email)) {
            $errors[] = 'E-mail é obrigatório.';
        }

        if (empty($senha)) {
            $errors[] = 'Senha é obrigatória.';
        }

        // Validar email real
        if (!empty($email) && !ValidatorHelper::validateEmail($email)) {
            $errors[] = 'E-mail inválido.';
        }

        // Validar comprimento mínimo de senha
        if (!empty($senha) && strlen($senha) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => [
                'email' => $email,
                'senha' => $senha
            ]
        ];
    }

    /**
     * Valida dados de cadastro
     */
    public static function validateCadastro($data) {
        $errors = [];

        $nome = trim($data['nome'] ?? '');
        $email = trim($data['email'] ?? '');
        $cpf = preg_replace('/\D/', '', $data['cpf'] ?? '');
        $telefone = preg_replace('/\D/', '', $data['telefone'] ?? '');
        $senha = $data['senha'] ?? '';

        // Verificar campos vazios
        if (empty($nome)) {
            $errors[] = 'Nome é obrigatório.';
        }

        if (empty($email)) {
            $errors[] = 'E-mail é obrigatório.';
        }

        if (empty($cpf)) {
            $errors[] = 'CPF é obrigatório.';
        }

        if (empty($telefone)) {
            $errors[] = 'Telefone é obrigatório.';
        }

        if (empty($senha)) {
            $errors[] = 'Senha é obrigatória.';
        }

        // Validar nome real
        if (!empty($nome) && !ValidatorHelper::validateNome($nome)) {
            $errors[] = 'Nome deve ter entre 3 e 255 caracteres com apenas letras.';
        }

        // Validar email real
        if (!empty($email) && !ValidatorHelper::validateEmail($email)) {
            $errors[] = 'E-mail inválido.';
        }

        // Validar CPF com dígito verificador (desabilitado por enquanto)
        // if (!empty($cpf) && !ValidatorHelper::validateCPF($cpf)) {
        //     $errors[] = 'CPF inválido.';
        // }

        // Validar telefone real
        if (!empty($telefone) && !ValidatorHelper::validateTelefone($telefone)) {
            $errors[] = 'Telefone inválido. Use 10 dígitos (fixo) ou 11 (celular).';
        }

        // Validar senha - apenas mínimo de 6 caracteres por enquanto
        if (!empty($senha) && strlen($senha) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => [
                'nome' => $nome,
                'email' => $email,
                'cpf' => $cpf,
                'telefone' => $telefone,
                'senha' => $senha
            ]
        ];
    }

    /**
     * Valida dados de recuperação de senha
     */
    public static function validateRecuperacao($data) {
        $errors = [];

        $email = trim($data['email'] ?? '');
        $nova_senha = $data['nova_senha'] ?? '';

        if (empty($email)) {
            $errors[] = 'E-mail é obrigatório.';
        }

        if (empty($nova_senha)) {
            $errors[] = 'A nova senha é obrigatória.';
        }

        if (!empty($email) && !ValidatorHelper::validateEmail($email)) {
            $errors[] = 'E-mail inválido.';
        }

        if (!empty($nova_senha) && strlen($nova_senha) < 6) {
            $errors[] = 'A nova senha deve ter no mínimo 6 caracteres.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => [
                'email' => $email,
                'nova_senha' => $nova_senha
            ]
        ];
    }
}
?>

