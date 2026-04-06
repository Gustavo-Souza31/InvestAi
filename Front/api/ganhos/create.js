async function criarGanho() {
    const descricao = document.getElementById('ganho-descricao').value.trim();
    const valor     = document.getElementById('ganho-valor').value;
    const data      = document.getElementById('ganho-data').value;
    const fixo      = document.getElementById('ganho-fixo').checked;

    if (!descricao || !valor || !data) {
        showAlert('Preencha todos os campos antes de enviar.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('descricao',  descricao);
    formData.append('valor',      parseFloat(valor));
    formData.append('data_ganho', data);
    formData.append('fixo',       fixo ? 1 : 0);


    try {
        const resposta = await fetch('../backend/api/ganhos/create.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao criar ganho.', 'error');
            return;
        }


        document.getElementById('ganho-valor').value     = '';
        document.getElementById('ganho-data').value      = new Date().toISOString().split('T')[0];
        document.getElementById('ganho-fixo').checked    = false;

        if (typeof carregarGanhos === 'function') {
            carregarGanhos();
        }
    } catch (error) {
        console.error('Erro ao criar ganho:', error);
        showAlert('Erro de conexão com o servidor.', 'error');
    }
}


document.getElementById('form-ganho')?.addEventListener('submit', function(e) {
    e.preventDefault();
    criarGanho();
});
