async function abrirModalAporte(metaId, metaNome) {
    document.getElementById('aporte-meta-id').value = metaId;
    document.getElementById('aporte-meta-nome').textContent = metaNome;
    document.getElementById('aporte-valor').value = '';
    document.getElementById('aporte-overlay').classList.add('active');
    carregarAportes(metaId);
}

async function registrarAporte() {
    const metaId   = document.getElementById('aporte-meta-id').value;
    const valorStr = document.getElementById('aporte-valor').value.trim();
    const valor    = parseFloat(valorStr);
    const dataAporte = document.getElementById('aporte-data').value || new Date().toISOString().split('T')[0];

    if (!metaId) {
        showAlert('ID da meta não foi definido.', 'error');
        return;
    }
    if (valorStr === '') {
        showAlert('Preencha o valor do aporte.', 'error');
        document.getElementById('aporte-valor').focus();
        return;
    }
    if (isNaN(valor)) {
        showAlert('Informe um valor numérico válido.', 'error');
        document.getElementById('aporte-valor').focus();
        return;
    }
    if (valor <= 0) {
        showAlert('O valor do aporte deve ser maior que zero.', 'error');
        document.getElementById('aporte-valor').focus();
        return;
    }
    if (valor > 99999999.99) {
        showAlert('O valor é muito grande (máximo R$ 99.999.999,99).', 'error');
        document.getElementById('aporte-valor').focus();
        return;
    }

    let btn = document.querySelector('#form-aporte button[type="submit"]');
    if (!btn) btn = document.createElement('button');
    btn.disabled = true;

    try {
        const payload = { meta_id: parseInt(metaId), valor, data_aporte: dataAporte };
        const resposta = await fetch(BASE_PATH + '/backend/api/aportes/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const resultado = await resposta.json();

        if (resultado.status !== 'success') {
            showAlert(resultado.message || 'Erro ao registrar aporte.', 'error');
            return;
        }
        showAlert('Aporte registrado com sucesso!', 'success');
        document.getElementById('aporte-valor').value = '';
        carregarAportes(parseInt(metaId));
        if (typeof carregarMetas === 'function') carregarMetas();
        if (typeof carregarDashboard === 'function') carregarDashboard();
    } catch (error) {
        console.error('Erro ao registrar aporte:', error);
        showAlert('Erro de conexão.', 'error');
    } finally {
        btn.disabled = false;
    }
}

function fecharModalAporte() {
    document.getElementById('aporte-overlay').classList.remove('active');
}

document.getElementById('form-aporte')?.addEventListener('submit', function (e) {
    e.preventDefault();
    registrarAporte();
});
