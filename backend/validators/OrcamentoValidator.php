<?php
require_once __DIR__ . '/ValidatorHelper.php';

class OrcamentoValidator
{
    public static function validate(array $data): array
    {
        $errors = [];

        $categoria_id = isset($data['categoria_id']) ? intval($data['categoria_id']) : 0;
        $limite       = floatval(str_replace(',', '.', $data['limite'] ?? 0));
        $mes          = intval($data['mes'] ?? date('n'));
        $ano          = intval($data['ano'] ?? date('Y'));

        if ($categoria_id <= 0) {
            $errors[] = 'Categoria obrigatória.';
        }

        if ($limite <= 0) {
            $errors[] = 'O limite deve ser maior que zero.';
        } elseif (!ValidatorHelper::validateValor($limite)) {
            $errors[] = 'Valor de limite inválido.';
        }

        if ($mes < 1 || $mes > 12) {
            $errors[] = 'Mês inválido.';
        }

        if ($ano < 2000 || $ano > 2100) {
            $errors[] = 'Ano inválido.';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
            'data'   => [
                'categoria_id' => $categoria_id,
                'limite'       => round($limite, 2),
                'mes'          => $mes,
                'ano'          => $ano,
            ],
        ];
    }
}
?>
