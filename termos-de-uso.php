<?php
declare(strict_types=1);
// Não usa o banco nem a sessão: os documentos continuam acessíveis mesmo com o banco fora do ar.
require __DIR__ . '/includes/documentos-legais.php';

prepararRespostaHttp();

$contato = contatoDocumentoLegal();
$privacidade = h(urlRecurso('politica-de-privacidade.php'));

$secoes = [
    ['sobre', 'Sobre o Eventeen', <<<HTML
        <p>O Eventeen é uma plataforma web para gerenciar eventos acadêmicos e institucionais: divulgação, inscrição,
        controle de vagas, registro de presença e emissão de certificados com validação pública.</p>
        <p>O sistema é um <strong>projeto acadêmico</strong> desenvolvido por estudantes do curso de Análise e
        Desenvolvimento de Sistemas da Fatec Ourinhos. Nestes termos, "Eventeen" e "equipe Eventeen" se referem a esse
        projeto e à equipe que o mantém; "você" é qualquer pessoa que acessa o site ou cria uma conta.</p>
        HTML],
    ['aceitacao', 'Aceitação dos termos', <<<HTML
        <p>Ao criar uma conta ou usar o Eventeen, você declara que leu e concorda com estes Termos de Uso e com a
        <a href="{$privacidade}">Política de Privacidade</a>. Se não concordar, não crie uma conta nem use o sistema.</p>
        <p>As páginas públicas (página inicial e validação de certificado) podem ser consultadas sem conta.</p>
        HTML],
    ['conta', 'Sua conta', <<<HTML
        <ul>
            <li>Informe dados verdadeiros e mantenha-os atualizados em <strong>Minha conta</strong>. O nome informado é o
            que aparece nos certificados.</li>
            <li>A conta é pessoal. Guarde sua senha com cuidado e não a compartilhe; você é responsável pelo que for feito
            com ela.</li>
            <li>Se suspeitar de uso indevido, troque a senha imediatamente: as outras sessões abertas são encerradas.</li>
            <li>Há três perfis: <strong>participante</strong>, <strong>organizador</strong> (exige o código de acesso
            fornecido pela instituição) e <strong>administrador</strong>. Cada perfil só acessa as funções que lhe
            cabem.</li>
        </ul>
        HTML],
    ['uso', 'Uso permitido e proibido', <<<HTML
        <p>Você pode usar o Eventeen para consultar eventos, inscrever-se, acompanhar sua participação e obter
        certificados — ou, como organizador, para administrar os eventos sob sua responsabilidade.</p>
        <p>Não é permitido:</p>
        <ul>
            <li>criar contas falsas, se passar por outra pessoa ou fazer inscrições em nome de terceiros sem autorização;</li>
            <li>tentar acessar áreas, dados ou contas sem permissão, ou contornar os mecanismos de segurança;</li>
            <li>enviar requisições automatizadas em excesso ou qualquer ação que prejudique o funcionamento do sistema;</li>
            <li>publicar em eventos conteúdo ilegal, ofensivo, discriminatório ou que viole direitos de terceiros;</li>
            <li>alterar, falsificar ou usar de má-fé certificados emitidos pela plataforma.</li>
        </ul>
        HTML],
    ['inscricoes', 'Eventos, inscrições e pagamentos', <<<HTML
        <ul>
            <li>As informações de cada evento (programação, local, datas, vagas e valor) são definidas e mantidas pelos
            seus organizadores, que respondem por elas.</li>
            <li>A inscrição depende de vagas disponíveis e pode ser cancelada por você enquanto o evento permitir.</li>
            <li>O organizador pode alterar, cancelar ou encerrar um evento; as inscrições e os registros já feitos são
            preservados.</li>
            <li>O Eventeen <strong>não processa pagamentos</strong>: quando um evento é pago, a forma de pagamento,
            a confirmação e eventuais devoluções são combinadas com o organizador, que apenas registra a situação do
            pagamento no sistema.</li>
        </ul>
        HTML],
    ['certificados', 'Presença e certificados', <<<HTML
        <ul>
            <li>A presença é registrada pelos organizadores, por atividade.</li>
            <li>O certificado fica disponível para atividades concluídas em que sua presença foi registrada, com a carga
            horária da atividade.</li>
            <li>Cada certificado tem um código único. Qualquer pessoa com esse código pode confirmar sua autenticidade na
            página <strong>Validar certificado</strong>, que mostra o nome do participante, a atividade, o evento, a data e
            a carga horária.</li>
            <li>O reconhecimento das horas (por exemplo, como atividades complementares) depende das regras da sua
            instituição de ensino.</li>
        </ul>
        HTML],
    ['organizadores', 'Responsabilidades dos organizadores', <<<HTML
        <p>Quem organiza eventos no Eventeen se compromete a:</p>
        <ul>
            <li>manter corretas as informações dos eventos e registrar presenças com fidelidade;</li>
            <li>usar os dados dos participantes, palestrantes e parceiros apenas para a realização do evento;</li>
            <li>não exportar, divulgar ou reutilizar esses dados para outras finalidades sem base legal;</li>
            <li>respeitar a <a href="{$privacidade}">Política de Privacidade</a> e a Lei Geral de Proteção de Dados
            (Lei nº 13.709/2018).</li>
        </ul>
        HTML],
    ['propriedade', 'Propriedade intelectual', <<<HTML
        <p>O código-fonte, a marca, o logotipo e a identidade visual do Eventeen pertencem à equipe Eventeen. O uso do
        sistema não transfere nenhum desses direitos, e não é permitido copiar, modificar ou redistribuir o sistema sem
        autorização por escrito.</p>
        <p>Os conteúdos cadastrados nos eventos (textos, programação, nomes de palestrantes e parceiros) pertencem a quem
        os cadastrou.</p>
        HTML],
    ['disponibilidade', 'Disponibilidade e limitações', <<<HTML
        <p>Por ser um projeto acadêmico em desenvolvimento, o Eventeen é oferecido "no estado em que se encontra". A
        equipe se esforça para mantê-lo disponível e correto, mas não garante funcionamento contínuo ou livre de erros:
        o sistema pode ficar fora do ar para manutenção ou passar por mudanças.</p>
        <p>Recomendamos guardar uma cópia (PDF) dos seus certificados. A equipe não se responsabiliza por eventos, prazos
        ou compromissos que dependam exclusivamente da disponibilidade do sistema.</p>
        HTML],
    ['encerramento', 'Suspensão e encerramento da conta', <<<HTML
        <p>Contas que violem estes termos podem ser suspensas ou ter seu perfil alterado pelos administradores.</p>
        <p>Você pode pedir a exclusão da sua conta a qualquer momento {$contato}. Alguns registros, como certificados já
        emitidos, podem ser mantidos pelo tempo necessário para a validação pública e a prestação de contas dos eventos,
        conforme explicado na <a href="{$privacidade}">Política de Privacidade</a>.</p>
        HTML],
    ['alteracoes', 'Alterações destes termos', <<<HTML
        <p>Estes termos podem ser atualizados conforme o sistema evolui. A data da última atualização aparece no início
        da página. Quando houver mudança relevante, avisaremos no próprio sistema; continuar usando o Eventeen depois
        disso significa concordar com a nova versão.</p>
        HTML],
    ['legislacao', 'Legislação aplicável e contato', <<<HTML
        <p>Estes termos são regidos pelas leis da República Federativa do Brasil, incluindo o Marco Civil da Internet
        (Lei nº 12.965/2014), a Lei Geral de Proteção de Dados (Lei nº 13.709/2018) e, quando aplicável, o Código de
        Defesa do Consumidor.</p>
        <p>Dúvidas sobre estes termos podem ser enviadas {$contato}.</p>
        HTML],
];

renderDocumentoLegal(
    'termos',
    'Termos de Uso',
    'As regras para usar o Eventeen: o que você pode esperar da plataforma e o que esperamos de quem participa ou organiza eventos.',
    $secoes,
    [
        'indexar' => true,
        'canonico' => '/termos-de-uso.php',
        'descricao' => 'Termos de Uso do Eventeen, a plataforma de eventos acadêmicos da Fatec Ourinhos: conta, inscrições, certificados e responsabilidades.',
    ]
);
