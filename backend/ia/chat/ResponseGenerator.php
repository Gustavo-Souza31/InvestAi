<?php
/**
 * backend/ia/chat/ResponseGenerator.php
 *
 * Gera a resposta amigável (2ª chamada ao modelo local) a partir da mensagem original
 * e do resultado da tool executada. Fallback hardcoded se o modelo falhar.
 */

class ResponseGenerator {

    public function __construct(private OllamaClient $ollama) {}

    /**
     * Gera resposta amigável para o usuário.
    * Tenta via Ollama; se falhar, usa template local.
     */
    public function generate(string $mensagemOriginal, string $toolName, array $resultado, string $nome_usuario = ''): string {
        $nome_ctx = $nome_usuario !== ''
            ? "O nome do usuário é {$nome_usuario}. Use o nome de forma natural quando soar bem."
            : '';

        if ($toolName === 'conversa') {
            $prompt = <<<PROMPT
{$nome_ctx}
Mensagem do usuário: "{$mensagemOriginal}"

Responda de forma amigável, curta (1-3 frases) e descontraída. Use 1 emoji quando fizer sentido. Se for uma saudação, cumprimente de volta e ofereça ajuda financeira de forma natural.
PROMPT;
        } else {
            $resultado_str = json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $prompt = <<<PROMPT
{$nome_ctx}
O usuário enviou: "{$mensagemOriginal}"
Ação executada: {$toolName}
Resultado: {$resultado_str}

Gere uma resposta amigável e curta (1-3 frases) informando o resultado. Use 1 emoji relevante. Se o resultado for sucesso, confirme o que foi feito com os dados reais (valor, descrição, categoria). Se houve erro, explique o problema gentilmente e sugira o que o usuário pode fazer.
PROMPT;
        }

        $resposta = $this->ollama->callForText($prompt, $nome_usuario);
        return $resposta ?? $this->fallback($toolName, $resultado, $nome_usuario);
    }

    private function fallback(string $toolName, array $resultado, string $nome_usuario = ''): string {
        if (($resultado['tipo'] ?? '') === 'erro') {
            return $resultado['mensagem'] ?? 'Tive um problema ao executar isso. Pode tentar novamente? 😅';
        }

        if (($resultado['tipo'] ?? '') === 'precisa_confirmacao') {
            return $resultado['mensagem'] ?? 'Tem certeza que quer fazer isso? Essa ação não pode ser desfeita!';
        }

        switch ($toolName) {
            case 'criar_orcamento':
                return "Orçamento de R$ {$resultado['valor']} para {$resultado['categoria']} criado! ✅";
            case 'criar_despesa':
                return "Despesa de R$ {$resultado['valor']} ({$resultado['descricao']}) registrada! ✅";
            case 'consultar_gastos':
                return 'Aqui estão seus gastos do mês! 📊';
            case 'consultar_orcamentos':
                return 'Aqui estão seus orçamentos definidos! 📋';
            case 'editar_despesa':
                return "Despesa \"{$resultado['descricao']}\" atualizada para R$ {$resultado['valor']}! ✏️";
            case 'deletar_despesa':
                return "Despesa \"{$resultado['descricao']}\" (R$ {$resultado['valor']}) removida! 🗑️";
            case 'criar_ganho':
                return "Ganho de R$ {$resultado['valor']} ({$resultado['descricao']}) registrado! 💰";
            case 'editar_ganho':
                return "Ganho \"{$resultado['descricao']}\" atualizado para R$ {$resultado['valor']}! ✏️";
            case 'deletar_ganho':
                return "Ganho \"{$resultado['descricao']}\" (R$ {$resultado['valor']}) removido! 🗑️";
            case 'consultar_ganhos':
                return 'Aqui estão seus ganhos do mês! 💵';
            case 'editar_orcamento':
                return "Orçamento de {$resultado['categoria']} atualizado para R$ {$resultado['limite']}! ✏️";
            case 'deletar_orcamento':
                return "Orçamento de {$resultado['categoria']} removido! 🗑️";
            case 'deletar_todos_orcamentos':
                return "Pronto! {$resultado['apagados']} orçamento(s) apagado(s) — total de R$ {$resultado['soma']} removido. 🗑️";
            case 'deletar_todas_despesas':
                return "Pronto! {$resultado['apagadas']} despesa(s) apagada(s) — total de R$ {$resultado['soma']} removido. 🗑️";
            case 'deletar_todos_ganhos':
                return "Pronto! {$resultado['apagados']} ganho(s) apagado(s) — total de R$ {$resultado['soma']} removido. 🗑️";
            case 'resumo_dashboard':
                $saldo = $resultado['saldo'] ?? 0;
                $emoji = $saldo >= 0 ? '📈' : '📉';
                $sinal = $saldo >= 0 ? '+' : '';
                return "Resumo do mês: ganhos R$ {$resultado['total_ganhos']}, despesas R$ {$resultado['total_despesas']}, saldo {$sinal}R$ {$saldo}. {$emoji}";
            case 'criar_meta':
                return "Meta \"{$resultado['nome']}\" de R$ {$resultado['valor_total']} criada! 🎯";
            case 'editar_meta':
                return "Meta \"{$resultado['nome']}\" atualizada! ✏️";
            case 'deletar_meta':
                return "Meta \"{$resultado['nome']}\" removida! 🗑️";
            case 'deletar_todas_metas':
                return "Pronto! {$resultado['apagadas']} meta(s) apagada(s). 🗑️";
            case 'consultar_metas':
                return 'Aqui estão suas metas! 🎯';
            case 'criar_aporte':
                return "Aporte de R$ {$resultado['valor']} para \"{$resultado['meta_nome']}\" registrado! 💰";
            case 'editar_aporte':
                return "Aporte atualizado para R$ {$resultado['valor']} na meta \"{$resultado['meta_nome']}\"! ✏️";
            case 'deletar_aporte':
                return "Aporte de R$ {$resultado['valor']} na meta \"{$resultado['meta_nome']}\" removido! 🗑️";
            case 'consultar_aportes':
                return 'Aqui estão seus aportes! 💰';
            default:
                return 'Posso ajudar com orçamentos, despesas, ganhos, metas e consultas financeiras! 💬';
        }
    }
}
