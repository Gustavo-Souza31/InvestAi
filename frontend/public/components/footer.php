<!-- ===== FOOTER GLOBAL ===== -->
<?php if (empty($hide_footer)): ?>
<footer class="site-footer">
    <div class="footer-inner">

        <div class="footer-grid">

            <!-- Coluna 1: Brand -->
            <div class="footer-col footer-col-brand">
                <a href="dashboard.php" class="footer-logo">
                    Invest<span>AI</span>
                </a>
                <p class="footer-tagline">Sua inteligência financeira pessoal.</p>
                <a href="mailto:contato.investai@gmail.com" class="footer-email">
                    <i class="bi bi-envelope"></i> contato.investai@gmail.com
                </a>
            </div>

            <!-- Coluna 2: Aviso Legal (sutil) -->
            <div class="footer-col footer-col-disclaimer">
                <h4 class="footer-col-title">Aviso Legal</h4>
                <p>
                    A InvestAI não é uma instituição financeira, corretora ou banco.
                    As sugestões geradas pela plataforma são baseadas em notícias públicas
                    e têm caráter exclusivamente informativo, não constituindo consultoria
                    financeira oficial. O usuário é responsável por suas próprias decisões
                    de investimento e a plataforma não se responsabiliza por eventuais perdas.
                </p>
            </div>

            <!-- Coluna 3: Diretrizes -->
            <div class="footer-col footer-col-links">
                <h4 class="footer-col-title">Diretrizes</h4>
                <ul class="footer-directives">
                    <li>
                        <button class="footer-link" onclick="openLegalModal('modal-privacidade')">
                            Política de Privacidade
                        </button>
                    </li>
                    <li>
                        <button class="footer-link" onclick="openLegalModal('modal-termos')">
                            Termos de Uso
                        </button>
                    </li>
                </ul>
                <p class="footer-copy">© <?= date('Y') ?> InvestAI. Todos os direitos reservados.</p>
            </div>

        </div>

        <!-- Barra inferior: feito por -->
        <div class="footer-bottom">
            <span class="footer-made-label">Created by</span>
            <div class="footer-authors">
                <span>Afonso Braga</span>
                <span class="footer-dot">·</span>
                <span>Giovana Martins</span>
                <span class="footer-dot">·</span>
                <span>Gustavo Souza</span>
                <span class="footer-dot">·</span>
                <span>João Milanezi</span>
                <span class="footer-dot">·</span>
                <span>Lucas Álfaro</span>
            </div>
        </div>

    </div>
</footer>
<?php endif; ?>

<!-- ===== MODAL: POLÍTICA DE PRIVACIDADE ===== -->
<div class="modal-legal-overlay" id="modal-privacidade">
    <div class="modal-legal-card">

        <div class="modal-legal-header">
            <h2><i class="bi bi-shield-check"></i> Política de Privacidade</h2>
            <button class="modal-legal-close" onclick="closeLegalModal('modal-privacidade')" aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="modal-legal-body">
            <p class="legal-updated">Última atualização: <?= date('d/m/Y') ?></p>
            <p class="legal-intro">A InvestAI valoriza a privacidade dos seus usuários e está comprometida com a proteção dos seus dados pessoais, em conformidade com a Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018).</p>

            <h3>1. Quais dados coletamos</h3>
            <p>Para a criação e manutenção da sua conta, coletamos:</p>
            <ul>
                <li>Nome completo</li>
                <li>Endereço de e-mail</li>
                <li>CPF (armazenado de forma segura, com criptografia)</li>
                <li>Número de telefone</li>
                <li>Dados financeiros inseridos voluntariamente (despesas, ganhos, metas e orçamentos)</li>
            </ul>
            <p>Também coletamos dados de uso da plataforma de forma anonimizada, para melhoria contínua do serviço.</p>

            <h3>2. Como utilizamos seus dados</h3>
            <p>Seus dados são utilizados exclusivamente para:</p>
            <ul>
                <li>Criação e autenticação da sua conta</li>
                <li>Personalização da experiência na plataforma</li>
                <li>Geração de análises e sugestões financeiras com base nas suas informações</li>
                <li>Comunicações relacionadas à conta (ex.: recuperação de senha)</li>
                <li>Melhoria dos algoritmos e da qualidade das sugestões geradas por IA</li>
            </ul>

            <h3>3. Compartilhamento de dados</h3>
            <p>A InvestAI <strong>não vende, aluga ou compartilha</strong> seus dados pessoais com terceiros para fins comerciais. Seus dados podem ser compartilhados apenas nas seguintes situações:</p>
            <ul>
                <li>Prestadores de serviços técnicos que auxiliam na operação da plataforma, sob contrato de confidencialidade</li>
                <li>Quando exigido por lei, ordem judicial ou autoridade competente</li>
            </ul>

            <h3>4. Segurança dos dados</h3>
            <p>Adotamos medidas técnicas e organizacionais adequadas para proteger seus dados contra acesso não autorizado, alteração, divulgação ou destruição. Senhas são armazenadas com hash criptográfico e nunca em texto puro. O acesso aos dados é restrito às pessoas e sistemas estritamente necessários.</p>

            <h3>5. Seus direitos (LGPD)</h3>
            <p>Conforme a LGPD, você tem direito a:</p>
            <ul>
                <li>Confirmar a existência de tratamento dos seus dados</li>
                <li>Acessar os dados que possuímos sobre você</li>
                <li>Solicitar a correção de dados incompletos ou incorretos</li>
                <li>Solicitar a anonimização, bloqueio ou eliminação de dados desnecessários</li>
                <li>Revogar o consentimento a qualquer momento</li>
                <li>Solicitar a exclusão total da sua conta e dados associados</li>
            </ul>
            <p>Para exercer esses direitos, entre em contato pelo e-mail de suporte disponível na plataforma.</p>

            <h3>6. Retenção de dados</h3>
            <p>Seus dados são mantidos enquanto sua conta estiver ativa. Após a exclusão da conta, os dados são eliminados dos nossos sistemas em até 30 dias, salvo obrigações legais que exijam retenção por período superior.</p>

            <h3>7. Cookies e sessões</h3>
            <p>A plataforma utiliza sessões e cookies essenciais para manter você autenticado e garantir o funcionamento correto das funcionalidades. Não utilizamos cookies para rastreamento publicitário ou compartilhamento com redes de anúncios.</p>

            <h3>8. Alterações desta política</h3>
            <p>Esta política pode ser atualizada periodicamente. Alterações relevantes serão comunicadas por notificação na plataforma. O uso continuado após a notificação implica na aceitação das alterações.</p>
        </div>

        <div class="modal-legal-footer-bar">
            <button class="btn-close-legal" onclick="closeLegalModal('modal-privacidade')">Entendi</button>
        </div>

    </div>
</div>

<!-- ===== MODAL: TERMOS DE USO ===== -->
<div class="modal-legal-overlay" id="modal-termos">
    <div class="modal-legal-card">

        <div class="modal-legal-header">
            <h2><i class="bi bi-file-text"></i> Termos de Uso</h2>
            <button class="modal-legal-close" onclick="closeLegalModal('modal-termos')" aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="modal-legal-body">
            <p class="legal-updated">Última atualização: <?= date('d/m/Y') ?></p>
            <p class="legal-intro">Ao criar uma conta e utilizar a InvestAI, você concorda integralmente com os presentes Termos de Uso. Caso não concorde com alguma das condições, não utilize a plataforma.</p>

            <h3>1. Sobre a plataforma</h3>
            <p>A InvestAI é uma plataforma de controle financeiro pessoal que oferece ferramentas para registro de despesas e ganhos, visualização de orçamentos, e sugestões de economia e investimento geradas por inteligência artificial com base em análise de notícias públicas.</p>

            <h3>2. Isenção de responsabilidade financeira</h3>
            <p><strong>A InvestAI não é uma instituição financeira, corretora de valores, banco ou assessor de investimentos.</strong> As sugestões geradas pela plataforma:</p>
            <ul>
                <li>São baseadas em análise automatizada de notícias públicas por IA</li>
                <li>Têm caráter exclusivamente informativo e educacional</li>
                <li>Não constituem recomendação, consultoria ou assessoria financeira de qualquer natureza</li>
                <li>Não devem ser a única base para decisões de investimento</li>
            </ul>
            <p>O usuário reconhece que todo investimento envolve riscos, incluindo a possibilidade de perda total do capital investido. <strong>A InvestAI não se responsabiliza por perdas, danos financeiros ou quaisquer prejuízos decorrentes de decisões tomadas com base nas informações da plataforma.</strong></p>

            <h3>3. Elegibilidade e cadastro</h3>
            <p>Para utilizar a InvestAI, você deve:</p>
            <ul>
                <li>Ter capacidade civil plena (ser maior de 18 anos ou legalmente emancipado)</li>
                <li>Fornecer informações verdadeiras e atualizadas no cadastro</li>
                <li>Manter suas credenciais de acesso em sigilo absoluto</li>
                <li>Notificar imediatamente qualquer uso não autorizado da sua conta</li>
            </ul>

            <h3>4. Uso permitido e proibido</h3>
            <p>Você pode utilizar a plataforma para controle financeiro pessoal legítimo. É expressamente proibido:</p>
            <ul>
                <li>Compartilhar, vender ou ceder sua conta a terceiros</li>
                <li>Tentar acessar áreas restritas ou dados de outros usuários</li>
                <li>Realizar engenharia reversa, descompilar ou modificar a plataforma</li>
                <li>Utilizar scripts automatizados, bots ou crawlers para acessar a plataforma</li>
                <li>Inserir dados falsos, fraudulentos ou enganosos</li>
                <li>Usar a plataforma para fins ilegais ou contrários à boa-fé</li>
            </ul>

            <h3>5. Propriedade intelectual</h3>
            <p>Todo o conteúdo da plataforma — incluindo código-fonte, design, textos, logos, algoritmos de IA e identidade visual — é de propriedade exclusiva da InvestAI e está protegido pela legislação de propriedade intelectual brasileira. É vedada a reprodução, distribuição ou uso não autorizado de qualquer elemento da plataforma.</p>

            <h3>6. Limitação de responsabilidade</h3>
            <p>A InvestAI não se responsabiliza por:</p>
            <ul>
                <li>Decisões financeiras ou de investimento tomadas pelo usuário</li>
                <li>Perdas decorrentes de interrupções, falhas técnicas ou indisponibilidade da plataforma</li>
                <li>Informações incorretas, incompletas ou desatualizadas provenientes de fontes de notícias externas</li>
                <li>Danos indiretos, incidentais ou consequenciais de qualquer natureza</li>
            </ul>

            <h3>7. Cancelamento e exclusão de conta</h3>
            <p>Você pode solicitar a exclusão da sua conta a qualquer momento pelo perfil da plataforma. A InvestAI reserva-se o direito de suspender ou encerrar contas que violem estes Termos de Uso, sem aviso prévio em casos graves ou fraudulentos.</p>

            <h3>8. Alterações nos termos</h3>
            <p>Estes Termos de Uso podem ser alterados a qualquer momento. Alterações significativas serão comunicadas com antecedência razoável via notificação na plataforma. O uso continuado após a vigência das alterações implica na sua aceitação.</p>

            <h3>9. Lei aplicável e foro</h3>
            <p>Estes Termos de Uso são regidos pelas leis da República Federativa do Brasil. Fica eleito o foro da comarca de domicílio do usuário para dirimir eventuais controvérsias decorrentes deste instrumento, salvo disposição legal em contrário.</p>
        </div>

        <div class="modal-legal-footer-bar">
            <button class="btn-close-legal" onclick="closeLegalModal('modal-termos')">Entendi</button>
        </div>

    </div>
</div>
