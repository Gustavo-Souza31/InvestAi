<?php
require_once __DIR__ . '/ValidatorHelper.php';

class MetasValidator
{
    public static function validate($data)
    {
        $errors = [];

        $nome = trim($data['nome'] ?? '');
        $valor_total = floatval(str_replace(',', '.', $data['valor_total'] ?? 0));
        $prazo = trim($data['prazo'] ?? '');

        if (empty($nome)) {
            $errors[] = 'Nome da meta é obrigatório.';
        } elseif (!ValidatorHelper::validateDescricao($nome)) {
            $errors[] = 'Nome inválido. Deve ter entre 3 e 255 caracteres.';
        }

        if ($valor_total <= 0) {
            $errors[] = 'Valor total deve ser maior que 0.';
        } elseif (!ValidatorHelper::validateValor($valor_total, 0.01, 99999999.99)) {
            $errors[] = 'Valor total inválido.';
        }

        // prazo opcional: aceitar vazio ou formato Y-m-d ou d/m/Y. Permitir futuro.
        if (!empty($prazo)) {
            // converter d/m/Y para Y-m-d
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $prazo, $m)) {
                $prazo = "{$m[3]}-{$m[2]}-{$m[1]}";
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $prazo)) {
                $errors[] = 'Prazo inválido. Use YYYY-MM-DD.';
            } else {
                list($y, $mo, $d) = explode('-', $prazo);
                if (!checkdate(intval($mo), intval($d), intval($y))) {
                    $errors[] = 'Prazo inválido.';
                }
            }
        } else {
            $prazo = null;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => [
                'nome' => $nome,
                'valor_total' => round($valor_total, 2),
                'prazo' => $prazo
            ]
        ];
    }
}
?>
