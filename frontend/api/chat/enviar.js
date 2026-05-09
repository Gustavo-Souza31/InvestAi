async function enviarMensagemChat(texto, mes = null, ano = null, historico = []) {
    const hoje = new Date();
    const body = {
        mensagem: texto,
        mes: mes ?? (hoje.getMonth() + 1),
        ano: ano ?? hoje.getFullYear(),
        historico: historico,
    };

    const response = await fetch('/inventai/backend/api/chat/mensagem.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        console.error('Erro no chat:', response.status);
        return null;
    }

    const data = await response.json();

    if (data.status === 'success') {
        return { resposta: data.resposta, acao: data.acao, precisa_confirmacao: data.precisa_confirmacao ?? false };
    }

    console.error('Resposta de erro do chat:', data.message);
    return null;
}

window.chatAPI = { enviarMensagem: enviarMensagemChat };
