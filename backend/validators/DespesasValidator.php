<?php
/**
 * DespesasValidator.php — Validações para despesas (com validações reais)
 */

require_once __DIR__ . '/ValidatorHelper.php';

class DespesasValidator {

    /**
     * Valida dados de criação/atualização de despesa
     */
    public static function validate($data) {
        $errors = [];

        $descricao = trim($data['descricao'] ?? '');
        $valor = floatval(str_replace(',', '.', $data['valor'] ?? 0));
        $data_despesa = trim($data['data_despesa'] ?? '');
        $fixo = intval($data['fixo'] ?? 0);

        // Verificar campos vazios
        if (empty($descricao)) {
            $errors[] = 'Descrição é obrigatória.';
        }

        if ($valor <= 0) {
            $errors[] = 'Valor deve ser maior que 0.';
        }

        if (empty($data_despesa)) {
            $errors[] = 'Data é obrigatória.';
        }

        // Validar descrição real (min 3 chars, max 255)
        if (!empty($descricao) && !ValidatorHelper::validateDescricao($descricao)) {
            $errors[] = 'Descrição deve ter entre 3 e 255 caracteres.';
        }

        // Validar valor monetário (max 999999.99)
        if ($valor > 0 && !ValidatorHelper::validateValor($valor)) {
            $errors[] = 'Valor inválido. Use formato numérico com até 2 casas decimais.';
        }

        // Validar data real
        if (!empty($data_despesa) && !ValidatorHelper::validateDataMovimentacao($data_despesa)) {
            $errors[] = 'Data inválida. Use formato YYYY-MM-DD ou DD/MM/YYYY.';
        }

        // Validar fixo (0 ou 1)
        if ($fixo !== 0 && $fixo !== 1) {
            $errors[] = 'Campo fixo deve ser 0 ou 1.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => [
                'descricao' => $descricao,
                'valor' => round($valor, 2),
                'data_despesa' => $data_despesa,
                'fixo' => $fixo
            ]
        ];
    }
}
?>
