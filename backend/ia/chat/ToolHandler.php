<?php
/**
 * backend/ia/chat/ToolHandler.php
 *
 * Registry e dispatcher das tools de chat.
 * Coleta definições para a Gemini API e despacha a execução.
 */

require_once __DIR__ . '/tools/despesas/ConsultarGastos.php';
require_once __DIR__ . '/tools/orcamentos/ConsultarOrcamentos.php';
require_once __DIR__ . '/tools/ganhos/ConsultarGanhos.php';
require_once __DIR__ . '/tools/comum/ResumoDashboard.php';
require_once __DIR__ . '/tools/despesas/CriarDespesa.php';
require_once __DIR__ . '/tools/despesas/EditarDespesa.php';
require_once __DIR__ . '/tools/despesas/DeletarDespesa.php';
require_once __DIR__ . '/tools/ganhos/CriarGanho.php';
require_once __DIR__ . '/tools/ganhos/EditarGanho.php';
require_once __DIR__ . '/tools/ganhos/DeletarGanho.php';
require_once __DIR__ . '/tools/orcamentos/CriarOrcamento.php';
require_once __DIR__ . '/tools/orcamentos/EditarOrcamento.php';
require_once __DIR__ . '/tools/orcamentos/DeletarOrcamento.php';
require_once __DIR__ . '/tools/orcamentos/DeletarTodosOrcamentos.php';
require_once __DIR__ . '/tools/despesas/DeletarTodasDespesas.php';
require_once __DIR__ . '/tools/ganhos/DeletarTodosGanhos.php';
require_once __DIR__ . '/tools/comum/PedirConfirmacao.php';
require_once __DIR__ . '/tools/comum/CategoriaResolver.php';
require_once __DIR__ . '/tools/comum/BulkDeleteHelper.php';
require_once __DIR__ . '/tools/comum/Conversa.php';
require_once __DIR__ . '/tools/comum/AcaoIndisponivel.php';

class ToolHandler {

    private array $registry = [
        'consultar_gastos'    => ConsultarGastos::class,
        'consultar_orcamentos'=> ConsultarOrcamentos::class,
        'consultar_ganhos'    => ConsultarGanhos::class,
        'resumo_dashboard'    => ResumoDashboard::class,
        'criar_despesa'       => CriarDespesa::class,
        'editar_despesa'      => EditarDespesa::class,
        'deletar_despesa'     => DeletarDespesa::class,
        'criar_ganho'         => CriarGanho::class,
        'editar_ganho'        => EditarGanho::class,
        'deletar_ganho'       => DeletarGanho::class,
        'criar_orcamento'     => CriarOrcamento::class,
        'editar_orcamento'    => EditarOrcamento::class,
        'deletar_orcamento'   => DeletarOrcamento::class,
        'deletar_todos_orcamentos' => DeletarTodosOrcamentos::class,
        'deletar_todas_despesas' => DeletarTodasDespesas::class,
        'deletar_todos_ganhos'   => DeletarTodosGanhos::class,
        'pedir_confirmacao'      => PedirConfirmacao::class,
        'conversa'            => Conversa::class,
        'acao_indisponivel'   => AcaoIndisponivel::class,
    ];

    /**
     * Retorna o array de function_declarations para a Gemini API,
     * coletado de getDefinition() de cada tool.
     */
    public function getAllDefinitions(mysqli $conexao, int $usuario_id, int $mes, int $ano): array {
        $definitions = [];
        foreach ($this->registry as $toolClass) {
            $tool          = new $toolClass($conexao, $usuario_id, $mes, $ano);
            $definitions[] = $tool->getDefinition();
        }
        return $definitions;
    }

    /**
     * Instancia a tool correspondente ao nome e executa com os parâmetros fornecidos.
     * Retorna o array de resultado da tool.
     */
    public function dispatch(string $toolName, array $params, mysqli $conexao, int $usuario_id, int $mes, int $ano): array {
        $toolClass = $this->registry[$toolName] ?? null;

        if (!$toolClass) {
            error_log("ToolHandler::dispatch - tool desconhecida: $toolName");
            return ['tipo' => 'conversa'];
        }

        $tool = new $toolClass($conexao, $usuario_id, $mes, $ano);
        return $tool->execute($params);
    }
}
