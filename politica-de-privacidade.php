<?php
declare(strict_types=1);
// Não usa o banco nem a sessão: os documentos continuam acessíveis mesmo com o banco fora do ar.
require __DIR__ . '/includes/documentos-legais.php';

prepararRespostaHttp();

$contato = contatoDocumentoLegal();
$termos = h(urlRecurso('termos-de-uso.php'));

$secoes = [
    ['quem-somos', 'Quem trata os seus dados', <<<HTML
        <p>Esta política explica quais dados pessoais o Eventeen coleta, para que os usa, com quem os compartilha e
        quais são os seus direitos, conforme a Lei Geral de Proteção de Dados Pessoais (LGPD, Lei nº 13.709/2018).</p>
        <p>Enquanto o Eventeen funciona como projeto acadêmico, a <strong>equipe Eventeen</strong> é responsável pelo
        tratamento dos dados. Quando uma instituição adotar o sistema, ela será a <strong>controladora</strong> dos dados
        dos seus eventos e a equipe Eventeen atuará como <strong>operadora</strong>, tratando os dados apenas conforme as
        instruções da instituição. Os dados cadastrados pela instituição pertencem a ela.</p>
        HTML],
    ['dados', 'Dados que coletamos', <<<HTML
        <table class="legal-tabela">
            <thead>
                <tr><th scope="col">Situação</th><th scope="col">Dados</th></tr>
            </thead>
            <tbody>
                <tr><td>Cadastro e conta</td><td>Nome completo, e-mail, matrícula (opcional), perfil de acesso e senha —
                guardada somente como <em>hash</em>, nunca em texto puro.</td></tr>
                <tr><td>Participação em eventos</td><td>Inscrições e cancelamentos, presença por atividade, certificados
                emitidos (com código de validação e carga horária) e, em eventos pagos, a situação do pagamento registrada
                pelo organizador (valor, forma e data).</td></tr>
                <tr><td>Organização de eventos</td><td>Dados de palestrantes (nome, e-mail e minicurrículo) e de parceiros
                e patrocinadores (nome, e-mail e telefone) cadastrados pelos organizadores.</td></tr>
                <tr><td>Segurança do acesso</td><td>E-mail e endereço IP de tentativas de login e de pedidos de
                recuperação de senha; código de redefinição de senha (guardado como <em>hash</em>).</td></tr>
                <tr><td>Funcionamento técnico</td><td>Cookie de sessão e registros técnicos de erro do servidor.</td></tr>
            </tbody>
        </table>
        <p>Não coletamos documentos de identidade, dados bancários, localização nem dados pessoais sensíveis.</p>
        HTML],
    ['finalidades', 'Para que usamos os dados', <<<HTML
        <ul>
            <li><strong>Criar e manter sua conta</strong> e permitir o acesso às funções do seu perfil.</li>
            <li><strong>Realizar os eventos:</strong> controlar vagas e inscrições, registrar presença, emitir e validar
            certificados e gerar relatórios para os organizadores.</li>
            <li><strong>Enviar e-mails do sistema</strong>, como confirmação de inscrição e recuperação de senha. Não
            enviamos propaganda.</li>
            <li><strong>Proteger o sistema</strong> contra acessos indevidos (limite de tentativas de login) e corrigir
            erros.</li>
        </ul>
        <p>As bases legais são a execução dos serviços que você solicita ao criar a conta e se inscrever (art. 7º, V, da
        LGPD), o legítimo interesse na segurança da plataforma (art. 7º, IX) e o cumprimento de obrigações legais
        (art. 7º, II), quando houver.</p>
        HTML],
    ['compartilhamento', 'Com quem compartilhamos', <<<HTML
        <ul>
            <li><strong>Organizadores dos eventos em que você se inscreve</strong> veem seu nome, e-mail e a situação
            da sua inscrição, presença e certificados naquele evento. Administradores do sistema também veem a matrícula e
            têm acesso para fins de gestão e suporte.</li>
            <li><strong>Validação pública de certificado:</strong> quem tiver o código de um certificado pode ver o nome do
            participante, a atividade, o evento, a data e a carga horária. Essas páginas não aparecem nos buscadores.</li>
            <li><strong>Fornecedores de infraestrutura</strong>, como a hospedagem do site e o serviço de envio de e-mails,
            somente na medida necessária para o sistema funcionar.</li>
            <li><strong>Autoridades</strong>, quando houver obrigação legal ou ordem judicial.</li>
        </ul>
        <p>O Eventeen <strong>não vende nem aluga</strong> dados pessoais e não os usa para publicidade.</p>
        HTML],
    ['cookies', 'Cookies e armazenamento no navegador', <<<HTML
        <p>Usamos apenas um <strong>cookie de sessão</strong>, essencial para manter você conectado. Ele expira após 2 horas
        sem uso; com a opção "Manter conectado", dura até 30 dias para participantes e 12 horas para organizadores e
        administradores.</p>
        <p>Quando o Eventeen é instalado como aplicativo, o navegador guarda apenas arquivos visuais (estilos, imagens e a
        página de "sem conexão"). Páginas com dados pessoais nunca são guardadas no aparelho.</p>
        <p>Não usamos cookies de publicidade, ferramentas de rastreamento nem serviços de análise de terceiros.</p>
        HTML],
    ['seguranca', 'Como protegemos os dados', <<<HTML
        <ul>
            <li>Conexão criptografada (HTTPS) e senhas armazenadas com <em>hash</em> (bcrypt).</li>
            <li>Acesso por perfil: cada pessoa vê apenas o que sua função permite, e organizadores só acessam os próprios
            eventos.</li>
            <li>Bloqueio temporário após várias tentativas de login erradas, expiração da sessão por inatividade e
            encerramento das outras sessões quando a senha é trocada.</li>
            <li>Links de recuperação de senha de uso único, válidos por 60 minutos.</li>
            <li>Proteção contra envio de formulários forjados (CSRF) e limites de tamanho em todos os campos.</li>
        </ul>
        <p>Nenhum sistema é totalmente imune a falhas. Se identificarmos um incidente de segurança que possa causar risco
        relevante, avisaremos as pessoas afetadas e a Autoridade Nacional de Proteção de Dados (ANPD), como determina a
        LGPD.</p>
        HTML],
    ['retencao', 'Por quanto tempo guardamos', <<<HTML
        <ul>
            <li><strong>Dados da conta:</strong> enquanto a conta existir.</li>
            <li><strong>Inscrições, presenças e certificados:</strong> enquanto forem necessários para comprovar a
            participação e permitir a validação pública dos certificados.</li>
            <li><strong>Registros financeiros dos eventos:</strong> pelo tempo necessário à prestação de contas.</li>
            <li><strong>Tentativas de acesso:</strong> apagadas automaticamente após 24 horas.</li>
            <li><strong>Códigos de redefinição de senha:</strong> deixam de valer após 60 minutos ou no primeiro uso.</li>
        </ul>
        <p>Ao fim desses prazos, os dados são excluídos ou anonimizados, salvo quando a lei exigir a sua conservação
        (art. 16 da LGPD).</p>
        HTML],
    ['direitos', 'Seus direitos', <<<HTML
        <p>A LGPD (art. 18) garante a você, entre outros, o direito de:</p>
        <ul>
            <li>confirmar se tratamos seus dados e acessá-los;</li>
            <li>corrigir dados incompletos, inexatos ou desatualizados;</li>
            <li>pedir a anonimização, o bloqueio ou a exclusão de dados desnecessários ou tratados em desacordo com a lei;</li>
            <li>pedir a portabilidade dos dados e informações sobre com quem eles foram compartilhados;</li>
            <li>pedir a exclusão da conta e apresentar reclamação à ANPD.</li>
        </ul>
        <p>Você mesmo pode atualizar nome, e-mail, matrícula e senha em <strong>Minha conta</strong> e cancelar inscrições
        em <strong>Minhas inscrições</strong>. Para os demais pedidos, entre em contato {$contato}. Responderemos no prazo
        previsto na LGPD.</p>
        HTML],
    ['alteracoes', 'Alterações desta política', <<<HTML
        <p>Esta política pode ser atualizada quando o sistema mudar ou a legislação exigir. A data da última atualização
        aparece no início da página, e mudanças relevantes serão avisadas no próprio sistema. Leia também os
        <a href="{$termos}">Termos de Uso</a>.</p>
        HTML],
    ['contato', 'Contato', <<<HTML
        <p>Dúvidas, pedidos sobre seus dados pessoais ou comunicações ao encarregado de proteção de dados podem ser
        enviados {$contato}.</p>
        HTML],
];

renderDocumentoLegal(
    'privacidade',
    'Política de Privacidade',
    'Quais dados o Eventeen coleta, por que coleta, com quem compartilha, como protege e como você exerce seus direitos.',
    $secoes,
    [
        'indexar' => true,
        'canonico' => '/politica-de-privacidade.php',
        'descricao' => 'Política de Privacidade do Eventeen: dados coletados, finalidades, compartilhamento, cookies, segurança e direitos previstos na LGPD.',
    ]
);
