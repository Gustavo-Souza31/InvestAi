/**
 * perfil.js — Lógica do perfil do usuário
 * Carrega dados, gerencia formulário e envia atualizações
 */

const API_BASE = '../backend/api/perfil';

// ===== ESTADO =====
let profileData = null;
let hasChanges = false;

// ===== INICIALIZAÇÃO =====
document.addEventListener('DOMContentLoaded', () => {
    loadProfile();
    setupEventListeners();
    setupCollapsible();
    setupPasswordStrength();
    setupPhoneMask();
});

// ===== CARREGAR PERFIL =====
async function loadProfile() {
    try {
        const res = await fetch(`${API_BASE}/read.php`);
        const data = await res.json();

        if (data.status !== 'success') {
            showToast(data.message || 'Erro ao carregar perfil.', 'error');
            return;
        }

        profileData = data;
        renderProfile(data);

        // Esconder loading, mostrar conteúdo
        document.getElementById('loading').style.display = 'none';
        document.getElementById('content').style.display = 'block';
    } catch (err) {
        console.error('Erro ao carregar perfil:', err);
        showToast('Erro de conexão com o servidor.', 'error');
    }
}

// ===== RENDERIZAR PERFIL =====
function renderProfile(data) {
    const u = data.usuario;
    const pf = data.perfil_financeiro;
    const stats = data.estatisticas;

    // Avatar (iniciais)
    const initials = u.nome.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
    document.getElementById('avatar-initials').textContent = initials;

    // Header info
    document.getElementById('profile-name').textContent = u.nome;
    document.getElementById('profile-email-display').textContent = u.email;

    // Member since
    const criado = new Date(u.criado_em);
    const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    document.getElementById('member-since').textContent = `Membro desde ${meses[criado.getMonth()]} ${criado.getFullYear()}`;

    // Stats
    document.getElementById('stat-ganhos').textContent = stats.count_ganhos;
    document.getElementById('stat-despesas').textContent = stats.count_despesas;
    document.getElementById('stat-saldo').textContent = formatMoney(stats.total_ganhos - stats.total_despesas);

    // Form fields — Dados pessoais
    document.getElementById('perfil-nome').value = u.nome;
    document.getElementById('perfil-email').value = u.email;
    document.getElementById('perfil-cpf').value = formatCPF(u.cpf);
    document.getElementById('perfil-telefone').value = formatPhone(u.telefone);

    // Form fields — Perfil financeiro
    if (pf) {
        document.getElementById('perfil-renda').value = pf.renda_mensal || '';
        document.getElementById('perfil-objetivo').value = pf.objetivo_financeiro || '';
        selectBehavior(pf.perfil_comportamento || 'moderado');
    } else {
        selectBehavior('moderado');
    }
}

// ===== FORMAT HELPERS =====
function formatCPF(cpf) {
    if (!cpf) return '';
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11) return cpf;
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function formatPhone(phone) {
    if (!phone) return '';
    phone = phone.replace(/\D/g, '');
    if (phone.length === 11) {
        return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (phone.length === 10) {
        return phone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    return phone;
}

function setupPhoneMask() {
    const input = document.getElementById('perfil-telefone');
    input.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 11) val = val.slice(0, 11);
        if (val.length >= 7) {
            e.target.value = val.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        } else if (val.length >= 3) {
            e.target.value = val.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        } else {
            e.target.value = val;
        }
        markChanged();
    });
}

// ===== BEHAVIOR PILLS =====
function selectBehavior(tipo) {
    document.querySelectorAll('.behavior-pill').forEach(pill => {
        pill.classList.toggle('active', pill.dataset.value === tipo);
    });
}

function getSelectedBehavior() {
    const active = document.querySelector('.behavior-pill.active');
    return active ? active.dataset.value : 'moderado';
}

// ===== COLLAPSIBLE SECTIONS =====
function setupCollapsible() {
    document.querySelectorAll('.section-header').forEach(header => {
        header.addEventListener('click', () => {
            const body = header.nextElementSibling;
            const isCollapsed = body.classList.contains('collapsed');

            if (isCollapsed) {
                body.classList.remove('collapsed');
                header.classList.remove('collapsed');
            } else {
                body.classList.add('collapsed');
                header.classList.add('collapsed');
            }
        });
    });
}

// ===== PASSWORD STRENGTH =====
function setupPasswordStrength() {
    const input = document.getElementById('perfil-nova-senha');
    const bars = document.querySelectorAll('.strength-bar');
    const label = document.getElementById('strength-label');

    input.addEventListener('input', () => {
        const val = input.value;
        let strength = 0;

        if (val.length >= 6) strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
        if (/\d/.test(val)) strength++;
        if (/[^a-zA-Z0-9]/.test(val)) strength++;

        // Reset
        bars.forEach(b => { b.className = 'strength-bar'; });
        label.className = 'strength-label';
        label.textContent = '';

        if (val.length === 0) return;

        if (strength <= 2) {
            bars[0].classList.add('weak');
            label.classList.add('weak');
            label.textContent = 'Fraca';
        } else if (strength <= 3) {
            bars[0].classList.add('medium');
            bars[1].classList.add('medium');
            label.classList.add('medium');
            label.textContent = 'Média';
        } else {
            bars[0].classList.add('strong');
            bars[1].classList.add('strong');
            bars[2].classList.add('strong');
            label.classList.add('strong');
            label.textContent = 'Forte';
        }

        markChanged();
    });
}

// ===== CHANGE TRACKING =====
function markChanged() {
    hasChanges = true;
    document.getElementById('btn-save').disabled = false;
    document.getElementById('btn-discard').style.opacity = '1';
}

function setupEventListeners() {
    // Track changes on inputs
    document.querySelectorAll('#content input:not([disabled]), #content select').forEach(input => {
        input.addEventListener('input', markChanged);
        input.addEventListener('change', markChanged);
    });

    // Behavior pills
    document.querySelectorAll('.behavior-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            selectBehavior(pill.dataset.value);
            markChanged();
        });
    });

    // Save button
    document.getElementById('btn-save').addEventListener('click', saveProfile);

    // Discard button
    document.getElementById('btn-discard').addEventListener('click', () => {
        if (profileData) {
            renderProfile(profileData);
            document.getElementById('perfil-senha-atual').value = '';
            document.getElementById('perfil-nova-senha').value = '';
            document.getElementById('perfil-confirma-senha').value = '';
            // Reset strength
            document.querySelectorAll('.strength-bar').forEach(b => b.className = 'strength-bar');
            document.getElementById('strength-label').textContent = '';
            hasChanges = false;
            document.getElementById('btn-save').disabled = true;
            document.getElementById('btn-discard').style.opacity = '0.5';
            showToast('Alterações descartadas.', 'error');
        }
    });
}

// ===== SALVAR PERFIL =====
async function saveProfile() {
    const btn = document.getElementById('btn-save');
    const originalContent = btn.innerHTML;

    // Validações
    const novaSenha = document.getElementById('perfil-nova-senha').value;
    const confirmaSenha = document.getElementById('perfil-confirma-senha').value;
    const senhaAtual = document.getElementById('perfil-senha-atual').value;

    if (novaSenha && novaSenha !== confirmaSenha) {
        showToast('As senhas não coincidem.', 'error');
        return;
    }

    if (novaSenha && !senhaAtual) {
        showToast('Informe a senha atual para alterar a senha.', 'error');
        return;
    }

    // Preparar FormData
    const formData = new FormData();
    formData.append('nome', document.getElementById('perfil-nome').value.trim());
    formData.append('email', document.getElementById('perfil-email').value.trim());
    formData.append('telefone', document.getElementById('perfil-telefone').value.trim());
    formData.append('renda_mensal', document.getElementById('perfil-renda').value || 0);
    formData.append('objetivo_financeiro', document.getElementById('perfil-objetivo').value.trim());
    formData.append('perfil_comportamento', getSelectedBehavior());

    if (novaSenha) {
        formData.append('senha_atual', senhaAtual);
        formData.append('nova_senha', novaSenha);
    }

    // Loading state
    btn.disabled = true;
    btn.innerHTML = '<div class="loading-spinner" style="width:18px;height:18px;border-width:2px;margin:0;"></div>Salvando...';

    try {
        const res = await fetch(`${API_BASE}/update.php`, {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.status === 'success') {
            showToast(data.message, 'success');

            // Atualizar nome na navbar
            if (data.nome) {
                const badge = document.querySelector('.user-badge');
                if (badge) {
                    badge.innerHTML = `<i class="bi bi-person-fill me-1"></i>${escapeHtml(data.nome)}`;
                }
                document.getElementById('profile-name').textContent = data.nome;
                const initials = data.nome.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
                document.getElementById('avatar-initials').textContent = initials;
            }

            // Clear password fields
            document.getElementById('perfil-senha-atual').value = '';
            document.getElementById('perfil-nova-senha').value = '';
            document.getElementById('perfil-confirma-senha').value = '';
            document.querySelectorAll('.strength-bar').forEach(b => b.className = 'strength-bar');
            document.getElementById('strength-label').textContent = '';

            hasChanges = false;
            document.getElementById('btn-discard').style.opacity = '0.5';

            // Recarregar dados
            loadProfile();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        console.error('Erro ao salvar:', err);
        showToast('Erro de conexão com o servidor.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
}

// ===== TOAST =====
function showToast(message, type = 'success') {
    // Remove existing
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
        ${message}
    `;

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}
