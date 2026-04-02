<?php
/**
 * ValidatorHelper.php — Funções auxiliares para validações reais
 */

class ValidatorHelper {

    /**
     * Valida CPF com algorítmo de dígito verificador
     * @param string $cpf CPF sem formatação (11 dígitos)
     * @return bool
     */
    public static function validateCPF($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        // Verificar tamanho
        if (strlen($cpf) !== 11) {
            return false;
        }

        // CPFs conhecidos como inválidos
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Verificar primeiro dígito
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($cpf[9]) !== $digit1) {
            return false;
        }

        // Verificar segundo dígito
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return intval($cpf[10]) === $digit2;
    }

    /**
     * Valida telefone brasileiro (10 ou 11 dígitos)
     * @param string $telefone Telefone sem formatação
     * @return bool
     */
    public static function validateTelefone($telefone) {
        $telefone = preg_replace('/\D/', '', $telefone);
        
        // Aceita 10 dígitos (fixo) ou 11 dígitos (celular)
        if (strlen($telefone) !== 10 && strlen($telefone) !== 11) {
            return false;
        }

        // Verificar DDD (deve estar entre 11 e 99)
        $ddd = intval(substr($telefone, 0, 2));
        if ($ddd < 11 || $ddd > 99) {
            return false;
        }

        // Se 11 dígitos, o 3º dígito deve ser 9 (celular)
        if (strlen($telefone) === 11 && intval($telefone[2]) !== 9) {
            return false;
        }

        return true;
    }

    /**
     * Valida data real (verifica se a data existe)
     * @param string $data Data em formato Y-m-d ou d/m/Y
     * @return bool
     */
    public static function validateData($data) {
        // Converter d/m/Y para Y-m-d
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $matches)) {
            $data = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        // Verificar formato Y-m-d
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return false;
        }

        // Verificar se data é válida
        list($year, $month, $day) = explode('-', $data);
        if (!checkdate(intval($month), intval($day), intval($year))) {
            return false;
        }

        // Verificar se data não é no futuro
        if (strtotime($data) > time()) {
            return false;
        }

        return true;
    }

    /**
     * Valida data de ganho/despesa (pode ter futuros, apenas não passado)
     * @param string $data Data em formato Y-m-d ou d/m/Y
     * @return bool
     */
    public static function validateDataMovimentacao($data) {
        // Converter d/m/Y para Y-m-d
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $matches)) {
            $data = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        // Verificar formato Y-m-d
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return false;
        }

        // Verificar se data é válida
        list($year, $month, $day) = explode('-', $data);
        if (!checkdate(intval($month), intval($day), intval($year))) {
            return false;
        }

        // Verificar se data não é muito antiga (máximo 10 anos atrás)
        $dataObj = strtotime($data);
        $minData = strtotime('-10 years');
        if ($dataObj < $minData) {
            return false;
        }

        return true;
    }

    /**
     * Valida descrição (min 3 caracteres, max 255)
     * @param string $descricao
     * @return bool
     */
    public static function validateDescricao($descricao) {
        $descricao = trim($descricao);
        $length = strlen($descricao);

        if ($length < 3) {
            return false;
        }

        if ($length > 255) {
            return false;
        }

        // Rejeitar apenas caracteres perigosos (tags HTML, etc)
        if (preg_match('/<|>|javascript|script|onclick|onerror|\$|`|\|;|\&\&|>\&/', $descricao, $matches)) {
            return false;
        }

        return true;
    }

    /**
     * Valida valor monetário
     * @param float|string $valor
     * @param float $min Valor mínimo
     * @param float $max Valor máximo
     * @return bool
     */
    public static function validateValor($valor, $min = 0.01, $max = 999999.99) {
        $valor = floatval(str_replace(',', '.', strval($valor)));

        if ($valor < $min || $valor > $max) {
            return false;
        }

        // Verificar se tem, no máximo, 2 casas decimais
        if (round($valor, 2) !== $valor) {
            return false;
        }

        return true;
    }

    /**
     * Valida nome (min 3 caracteres, max 255)
     * @param string $nome
     * @return bool
     */
    public static function validateNome($nome) {
        $nome = trim($nome);
        $length = strlen($nome);

        if ($length < 3) {
            return false;
        }

        if ($length > 255) {
            return false;
        }

        // Apenas letras, espaço e apóstrofo
        if (!preg_match('/^[a-zA-Záàâãéèêíïóôõöúçñ\s\']+$/', $nome)) {
            return false;
        }

        return true;
    }

    /**
     * Valida email com padrão mais robusto
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email) {
        $email = strtolower(trim($email));

        // Usar filter_var com FILTER_VALIDATE_EMAIL
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Verificar comprimento máximo
        if (strlen($email) > 254) {
            return false;
        }

        return true;
    }

    /**
     * Valida senha (min 8 caracteres, deve ter maiúscula, minúscula e número)
     * @param string $senha
     * @return bool
     */
    public static function validateSenha($senha) {
        $length = strlen($senha);

        // Comprimento mínimo de 8 caracteres
        if ($length < 8) {
            return false;
        }

        // Comprimento máximo de 50 caracteres
        if ($length > 50) {
            return false;
        }

        // Deve ter letra maiúscula
        if (!preg_match('/[A-Z]/', $senha)) {
            return false;
        }

        // Deve ter letra minúscula
        if (!preg_match('/[a-z]/', $senha)) {
            return false;
        }

        // Deve ter número
        if (!preg_match('/[0-9]/', $senha)) {
            return false;
        }

        return true;
    }
}
?>
