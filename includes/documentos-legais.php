<?php
declare(strict_types=1);

require_once __DIR__ . '/pagina.php';

/*
 * Estrutura comum dos documentos legais (Termos de Uso e Política de Privacidade):
 * cabeçalho público, aviso de versão preliminar, sumário e seções.
 */

const DOCUMENTOS_LEGAIS_VERSAO = '0.1 (preliminar)';
const DOCUMENTOS_LEGAIS_ATUALIZACAO = '25 de novembro de 2026';

/** Texto de contato usado nos documentos: o e-mail configurado ou, sem ele, a orientação geral. */
function contatoDocumentoLegal(): string
{
    $email = emailContato();
    if ($email !== '') {
        return 'pelo e-mail <a href="mailto:' . h($email) . '">' . h($email) . '</a>';
    }

    return 'pela organização do evento em que você se inscreveu, que encaminhará o pedido à equipe Eventeen';
}

/**
 * Escreve a página completa de um documento legal.
 *
 * @param string $atual     'termos' ou 'privacidade' (aba ativa).
 * @param string $titulo    Título do documento.
 * @param string $resumo    Parágrafo de abertura (texto simples).
 * @param array  $secoes    Lista de [id, título, html]; o HTML é escrito pela equipe, não vem do usuário.
 * @param array  $opcoesHead Opções de renderHeadPagina (descrição, canônico...).
 */
function renderDocumentoLegal(string $atual, string $titulo, string $resumo, array $secoes, array $opcoesHead): void
{
    $abas = [
        'termos' => ['termos-de-uso.php', 'Termos de uso'],
        'privacidade' => ['politica-de-privacidade.php', 'Política de privacidade'],
    ];
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderHeadPagina($titulo, $opcoesHead); ?>
    <link rel="stylesheet" href="<?= h(urlRecurso('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= h(urlRecurso('css/landing.css')) ?>">
    <link rel="stylesheet" href="<?= h(urlRecurso('css/legal.css')) ?>">
</head>
<body class="landing legal">
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

    <header class="lp-topo">
        <div class="lp-container lp-topo__barra">
            <a class="lp-topo__marca" href="<?= h(urlRecurso('index.php')) ?>" aria-label="Eventeen - página inicial">
                <img src="<?= h(urlRecurso('img/logo-eventeen-web.png')) ?>" alt="Eventeen" width="150" height="54">
            </a>
            <div class="lp-topo__acoes legal-topo__acoes">
                <a class="lp-link-entrar" href="<?= h(urlRecurso('entrar.php')) ?>">Entrar</a>
                <a class="lp-botao lp-botao--pequeno" href="<?= h(urlRecurso('cadastro.php')) ?>">Criar conta</a>
            </div>
        </div>
    </header>

    <main id="conteudo">
        <section class="legal-abertura" aria-labelledby="legal-titulo">
            <div class="lp-container">
                <nav class="legal-abas" aria-label="Documentos legais">
                    <?php foreach ($abas as $chave => [$url, $rotulo]): ?>
                        <a class="legal-abas__item<?= $chave === $atual ? ' legal-abas__item--ativo' : '' ?>" href="<?= h(urlRecurso($url)) ?>"<?= $chave === $atual ? ' aria-current="page"' : '' ?>><?= h($rotulo) ?></a>
                    <?php endforeach; ?>
                </nav>
                <h1 id="legal-titulo"><?= h($titulo) ?></h1>
                <p class="legal-abertura__resumo"><?= h($resumo) ?></p>
                <p class="legal-abertura__meta">Versão <?= h(DOCUMENTOS_LEGAIS_VERSAO) ?> · Última atualização: <?= h(DOCUMENTOS_LEGAIS_ATUALIZACAO) ?></p>
            </div>
        </section>

        <div class="lp-container legal-grade">
            <aside class="legal-sumario" aria-labelledby="legal-sumario-titulo">
                <p class="legal-sumario__titulo" id="legal-sumario-titulo">Nesta página</p>
                <ol>
                    <?php foreach ($secoes as $numero => [$id, $tituloSecao]): ?>
                        <li><a href="#<?= h($id) ?>"><?= h(($numero + 1) . '. ' . $tituloSecao) ?></a></li>
                    <?php endforeach; ?>
                </ol>
            </aside>

            <article class="legal-documento">
                <div class="legal-aviso" role="note">
                    <strong>Versão preliminar de um projeto acadêmico.</strong>
                    O Eventeen é desenvolvido por estudantes de Análise e Desenvolvimento de Sistemas da Fatec Ourinhos.
                    Este documento descreve como o sistema funciona hoje e ainda não passou por revisão jurídica;
                    ele será revisado antes de qualquer uso comercial ou institucional definitivo.
                </div>

                <?php foreach ($secoes as $numero => [$id, $tituloSecao, $html]): ?>
                    <section class="legal-secao" id="<?= h($id) ?>" aria-labelledby="<?= h($id) ?>-titulo">
                        <h2 id="<?= h($id) ?>-titulo"><span class="legal-secao__numero"><?= $numero + 1 ?>.</span> <?= h($tituloSecao) ?></h2>
                        <?= $html ?>
                    </section>
                <?php endforeach; ?>

                <p class="legal-voltar"><a href="#conteudo">Voltar ao topo ↑</a></p>
            </article>
        </div>
    </main>

    <?php renderRodapePublico(); ?>
</body>
</html>
    <?php
}
