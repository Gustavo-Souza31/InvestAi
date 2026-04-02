<?php
/**
 * IdValidator.php — Validações para IDs (deleção, atualização, etc)
 */

class IdValidator {

    /**
     * Valida se ID é válido para operações
     */
    public static function validateId($id) {
        $errors = [];

        $id = intval($id ?? 0);

        if ($id <= 0) {
            $errors[] = 'ID inválido.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'id' => $id
        ];
    }
}
?>
