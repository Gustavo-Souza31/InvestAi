document.addEventListener('DOMContentLoaded', () => {
    // Máscara CPF
    const cpfInput = document.getElementById('cadastro-cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            this.value = v;
        });
    }

    // Máscara Telefone
    const telInput = document.getElementById('cadastro-telefone');
    if (telInput) {
        telInput.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '');
            v = v.replace(/^(\d{2})(\d)/, '($1) $2');
            v = v.replace(/(\d{5})(\d{4})$/, '$1-$2');
            this.value = v;
        });
    }
});
