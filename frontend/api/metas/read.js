async function listarMetas() {
    try {
        const resposta = await fetch(BASE_PATH + '/backend/api/metas/read.php');
        const text = await resposta.text();
        try {
            return JSON.parse(text);
        } catch (error) {
            console.error('Resposta não é JSON:', text);
            return { status: 'error', message: 'Resposta não é JSON', raw: text };
        }
    } catch (error) {
        console.error('Erro ao listar metas:', error);
        return { status: 'error', message: 'Erro de conexão' };
    }
}

async function obterMeta(id) {
    try {
        const resposta = await fetch(BASE_PATH + '/backend/api/metas/read.php?id=' + id);
        return await resposta.json();
    } catch (error) {
        console.error('Erro ao obter meta:', error);
        return { status: 'error', message: 'Erro de conexão' };
    }
}
