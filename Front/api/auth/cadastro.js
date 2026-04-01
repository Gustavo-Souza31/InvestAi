/**
 * Efetuar cadastro
 * @param {string} nome - Nome completo
 * @param {string} email - Email
 * @param {string} cpf - CPF
 * @param {string} telefone - Telefone
 * @param {string} senha - Senha
 * @returns {Promise} Resultado do cadastro
 */
async function efetuarCadastro(nome, email, cpf, telefone, senha) {
    return await apiCall('../backend/api/auth/cadastro.php', {
        nome: nome,
        email: email,
        cpf: cpf,
        telefone: telefone,
        senha: senha
    });
}
