<?php
declare(strict_types=1);

/*
 * Elementos comuns das páginas: cabeçalho <head> (SEO, ícones e PWA) e páginas de erro.
 * Não depende do banco nem da sessão: as páginas de erro precisam funcionar mesmo
 * quando o banco estiver fora do ar.
 */
require_once __DIR__ . '/base.php';

const EVENTEEN_SLOGAN = 'do convite ao certificado';
const EVENTEEN_COR_TEMA = '#c0172d';
const EVENTEEN_DESCRICAO_PADRAO = 'Eventeen é a plataforma de eventos acadêmicos da Fatec Ourinhos: '
    . 'inscrições, controle de presença e certificados em um só lugar, do convite ao certificado.';

/** Endereço público de um caminho da aplicação (ex.: '/cadastro.php'). */
function urlPublica(string $caminho = '/'): string
{
    return applicationBaseUrl() . '/' . ltrim($caminho, '/');
}

/** Caminho na web de um arquivo estático da aplicação (ex.: 'css/style.css'), válido em qualquer pasta. */
function urlRecurso(string $caminho): string
{
    return applicationBasePath() . '/' . ltrim($caminho, '/');
}

/**
 * Escreve o título e as metatags de SEO, ícones e PWA dentro do <head>.
 * O <meta charset> e o <meta viewport> continuam na própria página, logo no início do <head>.
 *
 * Opções:
 *  - descricao: resumo exibido pelos buscadores (até ~160 caracteres);
 *  - indexar: true apenas nas páginas públicas que devem aparecer nos buscadores;
 *  - canonico: caminho da versão oficial da página (ex.: '/'), usado quando indexar = true;
 *  - titulo_completo: usa $titulo como está, sem o sufixo "| Eventeen: do convite ao certificado";
 *  - dados_estruturados: inclui os dados estruturados (JSON-LD) do site — só na página inicial.
 */
function renderHeadPagina(string $titulo, array $opcoes = []): void
{
    $tituloFinal = !empty($opcoes['titulo_completo']) ? $titulo : $titulo . ' | Eventeen: ' . EVENTEEN_SLOGAN;
    $descricao = (string) ($opcoes['descricao'] ?? EVENTEEN_DESCRICAO_PADRAO);
    $indexar = !empty($opcoes['indexar']);
    $canonico = $indexar && isset($opcoes['canonico']) ? urlPublica((string) $opcoes['canonico']) : null;
    $imagem = urlPublica('img/eventeen-compartilhar.png');
    ?>
    <title><?= h($tituloFinal) ?></title>
    <meta name="description" content="<?= h($descricao) ?>">
    <meta name="robots" content="<?= $indexar ? 'index, follow, max-image-preview:large' : 'noindex, nofollow' ?>">
    <?php if ($canonico !== null): ?>
    <link rel="canonical" href="<?= h($canonico) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="<?= EVENTEEN_COR_TEMA ?>">
    <meta name="application-name" content="Eventeen">
    <meta name="apple-mobile-web-app-title" content="Eventeen">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">
    <link rel="icon" href="<?= h(urlRecurso('favicon.ico')) ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= h(urlRecurso('img/icones/favicon-32.png')) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= h(urlRecurso('img/icones/favicon-16.png')) ?>">
    <link rel="apple-touch-icon" href="<?= h(urlRecurso('img/icones/apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= h(urlRecurso('manifest.json')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Eventeen">
    <meta property="og:title" content="<?= h($tituloFinal) ?>">
    <meta property="og:description" content="<?= h($descricao) ?>">
    <meta property="og:image" content="<?= h($imagem) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Eventeen: <?= h(EVENTEEN_SLOGAN) ?>">
    <?php if ($canonico !== null): ?>
    <meta property="og:url" content="<?= h($canonico) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($tituloFinal) ?>">
    <meta name="twitter:description" content="<?= h($descricao) ?>">
    <meta name="twitter:image" content="<?= h($imagem) ?>">
    <?php if (!empty($opcoes['dados_estruturados'])): ?>
    <script type="application/ld+json"><?= dadosEstruturadosSite() ?></script>
    <?php endif; ?>
    <script src="<?= h(urlRecurso('js/pwa.js')) ?>" defer></script>
    <?php
}

/**
 * Botão "Instalar o app" (PWA). Fica oculto e só aparece quando o navegador permite instalar
 * (js/pwa.js); no iPhone/iPad, mostra a instrução para adicionar à Tela de Início.
 */
function renderInstalarApp(string $classe = 'instalar-app'): void
{
    ?>
    <div class="<?= h($classe) ?>" data-instalar-app hidden>
        <button class="botao botao--secundario" type="button" data-instalar-app-botao hidden>Instalar o app Eventeen</button>
        <p class="instalar-app__dica" data-instalar-app-ios hidden>Para instalar no iPhone ou iPad, toque em <strong>Compartilhar</strong> e depois em <strong>Adicionar à Tela de Início</strong>.</p>
    </div>
    <?php
}

/** E-mail de contato publicado nos Termos de Uso e na Política de Privacidade ('' quando não configurado). */
function emailContato(): string
{
    $email = trim((string) appConfig('email_contato'));

    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

/** Rodapé das páginas públicas (página inicial e documentos legais). */
function renderRodapePublico(): void
{
    ?>
    <footer class="lp-rodape">
        <div class="lp-container lp-rodape__grade">
            <div>
                <img src="<?= h(urlRecurso('img/logo-eventeen-web.png')) ?>" alt="Eventeen" width="130" height="47">
                <p>Do convite ao certificado.</p>
            </div>
            <nav aria-label="Links do rodapé">
                <a href="<?= h(urlRecurso('entrar.php')) ?>">Entrar</a>
                <a href="<?= h(urlRecurso('cadastro.php')) ?>">Criar conta</a>
                <a href="<?= h(urlRecurso('validar-certificado.php')) ?>">Validar certificado</a>
                <a href="<?= h(urlRecurso('termos-de-uso.php')) ?>">Termos de uso</a>
                <a href="<?= h(urlRecurso('politica-de-privacidade.php')) ?>">Política de privacidade</a>
            </nav>
            <p class="lp-rodape__assinatura">Fatec Ourinhos · Análise e Desenvolvimento de Sistemas · <?= date('Y') ?></p>
        </div>
    </footer>
    <?php
}

/** Assinatura das telas de acesso (login, recuperação de senha, erros), com os links legais. */
function renderAssinaturaAcesso(): void
{
    ?>
    <footer class="assinatura">
        Fatec Ourinhos ·
        <a href="<?= h(urlRecurso('termos-de-uso.php')) ?>">Termos de uso</a> ·
        <a href="<?= h(urlRecurso('politica-de-privacidade.php')) ?>">Privacidade</a>
    </footer>
    <?php
}

/** Dados estruturados (schema.org) do site, para os buscadores entenderem o que é o Eventeen. */
function dadosEstruturadosSite(): string
{
    $url = urlPublica('/');
    $dados = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => $url . '#site',
                'url' => $url,
                'name' => 'Eventeen',
                'alternateName' => 'Eventeen: ' . EVENTEEN_SLOGAN,
                'description' => EVENTEEN_DESCRICAO_PADRAO,
                'inLanguage' => 'pt-BR',
            ],
            [
                '@type' => 'WebApplication',
                '@id' => $url . '#aplicacao',
                'name' => 'Eventeen',
                'url' => $url,
                'description' => EVENTEEN_DESCRICAO_PADRAO,
                'applicationCategory' => 'EducationalApplication',
                'operatingSystem' => 'Web, Android, iOS, Windows, macOS',
                'inLanguage' => 'pt-BR',
                'image' => urlPublica('img/eventeen-compartilhar.png'),
                'logo' => urlPublica('img/icones/icone-512.png'),
            ],
        ],
    ];

    // JSON_HEX_TAG impede que um "</script>" em algum valor feche o bloco antes da hora.
    return (string) json_encode($dados, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

/** Títulos e mensagens padrão de cada código de erro HTTP. */
function mensagensErroHttp(int $codigo): array
{
    return match ($codigo) {
        400 => ['Solicitação inválida', 'Não conseguimos entender a solicitação. Atualize a página e tente novamente.'],
        401 => ['Entre na sua conta', 'Para ver esta página, é preciso entrar com seu e-mail e senha.'],
        403 => ['Acesso negado', 'Você não tem permissão para acessar esta página. Se acha que isso é um engano, fale com a organização do evento.'],
        404 => ['Página não encontrada', 'O endereço pode ter sido digitado errado, ou a página foi removida ou mudou de lugar.'],
        405 => ['Ação não permitida', 'Esta página não aceita esse tipo de acesso. Volte e use os botões da própria página.'],
        408 => ['Tempo esgotado', 'A página demorou demais para responder. Verifique sua conexão e tente novamente.'],
        413 => ['Dados grandes demais', 'O formulário enviado excede o tamanho permitido. Volte, revise os campos e tente novamente.'],
        429 => ['Muitas tentativas', 'Recebemos muitas solicitações em pouco tempo. Aguarde alguns minutos e tente novamente.'],
        502, 503, 504 => ['Serviço indisponível', 'O Eventeen está temporariamente fora do ar, talvez em manutenção. Tente novamente em alguns minutos.'],
        default => ['Algo deu errado', 'Não foi possível concluir a operação agora. Tente novamente em alguns instantes. Se o problema continuar, avise a organização do evento.'],
    };
}

/** Códigos de erro com página própria; os demais são tratados como erro interno (500). */
function codigoErroHttpValido(int $codigo): int
{
    return in_array($codigo, [400, 401, 403, 404, 405, 408, 413, 429, 500, 502, 503, 504], true) ? $codigo : 500;
}

/** Escreve a página de erro completa do Eventeen (sem enviar cabeçalhos HTTP). */
function renderPaginaErro(int $codigo, ?string $mensagem = null): void
{
    $codigo = codigoErroHttpValido($codigo);
    [$titulo, $mensagemPadrao] = mensagensErroHttp($codigo);
    $mensagem = $mensagem !== null && trim($mensagem) !== '' ? $mensagem : $mensagemPadrao;
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderHeadPagina($titulo, ['descricao' => $mensagem]); ?>
    <link rel="stylesheet" href="<?= h(urlRecurso('css/style.css')) ?>">
</head>
<body>
    <main class="auth-page erro-pagina">
        <a class="marca" href="<?= h(urlRecurso('index.php')) ?>" aria-label="Eventeen - página inicial">
            <img class="logo" src="<?= h(urlRecurso('img/logo-eventeen-web.png')) ?>" alt="Eventeen" width="190" height="68">
        </a>

        <section class="auth-card erro-card" aria-labelledby="titulo">
            <p class="erro-codigo" aria-hidden="true"><?= $codigo ?></p>
            <header class="auth-header">
                <p class="eyebrow">Erro <?= $codigo ?></p>
                <h1 id="titulo"><?= h($titulo) ?></h1>
                <p class="subtitulo"><?= h($mensagem) ?></p>
            </header>

            <div class="erro-acoes">
                <?php if ($codigo === 408 || $codigo >= 500): ?>
                    <a class="botao" href="<?= h((string) ($_SERVER['REQUEST_URI'] ?? urlRecurso('index.php'))) ?>">Tentar novamente</a>
                <?php else: ?>
                    <a class="botao" href="<?= h(urlRecurso('index.php')) ?>">Ir para o início</a>
                <?php endif; ?>
                <button class="botao botao--secundario erro-voltar" type="button" hidden>Voltar à página anterior</button>
            </div>

            <?php if ($codigo === 404): ?>
                <p class="rodape">Recebeu um certificado? <a href="<?= h(urlRecurso('validar-certificado.php')) ?>">Validar certificado</a></p>
            <?php endif; ?>
        </section>

        <?php renderAssinaturaAcesso(); ?>
    </main>
    <script>
        (function () {
            var botao = document.querySelector('.erro-voltar');
            if (botao && window.history.length > 1) {
                botao.hidden = false;
                botao.addEventListener('click', function () { window.history.back(); });
            }
        })();
    </script>
</body>
</html>
    <?php
}

/** Interrompe a requisição exibindo a página de erro do Eventeen com o código HTTP informado. */
function encerrarComErro(int $codigo, ?string $mensagem = null): never
{
    if (!headers_sent()) {
        http_response_code($codigo);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }
    renderPaginaErro($codigo, $mensagem);
    exit;
}
