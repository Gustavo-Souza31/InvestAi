document.addEventListener('DOMContentLoaded', () => {
    // Basic interaction for voice command button
    const btnVoiceCommand = document.getElementById('btnVoiceCommand');
    
    if (btnVoiceCommand) {
        btnVoiceCommand.addEventListener('click', () => {
            // Check if SpeechRecognition is supported
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            
            if (SpeechRecognition) {
                const recognition = new SpeechRecognition();
                recognition.lang = 'pt-BR'; // Set language to Brazilian Portuguese
                
                // Change UI to indicate listening state
                const originalContent = btnVoiceCommand.innerHTML;
                btnVoiceCommand.innerHTML = '<i class="bi bi-mic-fill fs-5 text-danger spinner-grow spinner-grow-sm"></i> Ouvindo...';
                btnVoiceCommand.classList.add('border-danger');
                
                recognition.start();
                
                recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript;
                    console.log('Voice Input:', transcript);
                    
                    // TODO: Send to PHP/Node backend for AI processing
                    alert('Você disse: ' + transcript + '\nEm breve isso será enviado para a IA classificar sua despesa!');
                };
                
                recognition.onspeechend = () => {
                    recognition.stop();
                    resetVoiceButton();
                };
                
                recognition.onerror = (event) => {
                    console.error('Speech recognition error', event.error);
                    alert('Erro no microfone: ' + event.error);
                    resetVoiceButton();
                };
                
                function resetVoiceButton() {
                    btnVoiceCommand.innerHTML = originalContent;
                    btnVoiceCommand.classList.remove('border-danger');
                }
            } else {
                alert('Seu navegador não suporta reconhecimento de voz.');
            }
        });
    }
});
