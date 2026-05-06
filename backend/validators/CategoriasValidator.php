<?php
class CategoriasValidator
{
    private static function validarNome(string $nome): bool
    {
        $len = mb_strlen($nome);
        if ($len < 2 || $len > 100) return false;
        if (preg_match('/<|>|javascript|script|onclick|onerror|\$|`|\|;/', $nome)) return false;
        return true;
    }

    public static function validateCreate(array $data): array
    {
        $errors = [];
        $nome = trim($data['nome'] ?? '');
        $tipo = $data['tipo'] ?? '';

        if (empty($nome)) {
            $errors[] = 'Nome da categoria é obrigatório.';
        } elseif (!self::validarNome($nome)) {
            $errors[] = 'Nome deve ter entre 2 e 100 caracteres e não conter caracteres inválidos.';
        }

        if (!in_array($tipo, ['ganho', 'despesa'], true)) {
            $errors[] = 'Tipo deve ser "ganho" ou "despesa".';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
            'data'   => ['nome' => $nome, 'tipo' => $tipo],
        ];
    }

    public static function validateUpdate(array $data): array
    {
        $errors = [];
        $id   = isset($data['id']) ? intval($data['id']) : 0;
        $nome = trim($data['nome'] ?? '');

        if ($id <= 0) {
            $errors[] = 'ID inválido.';
        }

        if (empty($nome)) {
            $errors[] = 'Nome da categoria é obrigatório.';
        } elseif (!self::validarNome($nome)) {
            $errors[] = 'Nome deve ter entre 2 e 100 caracteres e não conter caracteres inválidos.';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
            'data'   => ['id' => $id, 'nome' => $nome],
        ];
    }

    public static function validateDelete(array $data): array
    {
        $errors = [];
        $id = isset($data['id']) ? intval($data['id']) : 0;

        if ($id <= 0) {
            $errors[] = 'ID inválido.';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
            'data'   => ['id' => $id],
        ];
    }
}
?>
