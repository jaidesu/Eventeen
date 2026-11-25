<?php
declare(strict_types=1);

/*
 * Configuração da aplicação: os valores e as funções de base ficam em includes/base.php.
 * Em um servidor, copie config.exemplo.php para config.local.php (fora do Git).
 */
require_once __DIR__ . '/includes/base.php';
require_once __DIR__ . '/includes/pagina.php';

/*
 * Limites de tamanho das entradas (proteção contra envio de dados excessivos).
 * O banco e cada formulário têm limites próprios; estes valem para qualquer requisição.
 */
const REQUISICAO_TAMANHO_MAXIMO = 1048576;   // 1 MB por requisição (não há envio de arquivos)
const CAMPO_TAMANHO_MAXIMO = 65535;          // maior campo do banco (TEXT)
const ENTRADA_PROFUNDIDADE_MAXIMA = 2;       // ex.: rateios[3] ou participantes[]
const ENTRADA_ITENS_MAXIMOS = 900;           // valores somados de GET e POST (abaixo do max_input_vars padrão, 1000)
const SENHA_TAMANHO_MINIMO = 8;
const SENHA_TAMANHO_MAXIMO = 64;             // senhas novas (o bcrypt considera até 72 bytes)
const SENHA_LOGIN_TAMANHO_MAXIMO = 128;      // senhas digitadas para entrar
const BUSCA_TAMANHO_MAXIMO = 100;

/** Recusa requisições com corpo grande demais ou campos fora dos limites, antes de qualquer processamento. */
function validarTamanhoRequisicao(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    $excesso = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > REQUISICAO_TAMANHO_MAXIMO;
    // Ao atingir max_input_vars o PHP descarta o excedente em silêncio: trata-se como excesso.
    $limiteItens = min(ENTRADA_ITENS_MAXIMOS, max(1, (int) ini_get('max_input_vars')) - 1);
    $itens = 0;
    $verificar = static function (array $valores, int $nivel) use (&$verificar, &$itens, $limiteItens): bool {
        foreach ($valores as $chave => $valor) {
            $itens++;
            if ($itens > $limiteItens || strlen((string) $chave) > 100) {
                return false;
            }
            if (is_array($valor)) {
                if ($nivel >= ENTRADA_PROFUNDIDADE_MAXIMA || !$verificar($valor, $nivel + 1)) {
                    return false;
                }
            } elseif (strlen((string) $valor) > CAMPO_TAMANHO_MAXIMO) {
                return false;
            }
        }

        return true;
    };
    if ($excesso || !$verificar($_GET, 0) || !$verificar($_POST, 0)) {
        encerrarComErro(413);
    }
}

validarTamanhoRequisicao();

/** Texto de busca: aparado e cortado no tamanho máximo, sem erro para o usuário. */
function termoDeBusca(mixed $valor): string
{
    return is_string($valor) ? mb_substr(trim($valor), 0, BUSCA_TAMANHO_MAXIMO, 'UTF-8') : '';
}

/** Valida uma senha nova; devolve a mensagem de erro ou null quando está aceitável. */
function erroNovaSenha(string $senha, string $confirmacao): ?string
{
    $tamanho = mb_strlen($senha, 'UTF-8');
    if ($tamanho < SENHA_TAMANHO_MINIMO || $tamanho > SENHA_TAMANHO_MAXIMO || strlen($senha) > 72) {
        return 'A senha deve ter entre ' . SENHA_TAMANHO_MINIMO . ' e ' . SENHA_TAMANHO_MAXIMO . ' caracteres.';
    }
    if ($senha !== $confirmacao) {
        return 'A confirmação não corresponde à nova senha.';
    }

    return null;
}

/** Atributos HTML padronizados para campos de senha nova. */
function atributosSenhaNova(): string
{
    return 'minlength="' . SENHA_TAMANHO_MINIMO . '" maxlength="' . SENHA_TAMANHO_MAXIMO . '"';
}

error_reporting(E_ALL);
ini_set('log_errors', '1');
if (appEmProducao()) {
    ini_set('display_errors', '0');
    ini_set('error_log', appPastaDados('logs') . '/php-erros.log');
    if (PHP_SAPI !== 'cli') {
        set_exception_handler(static function (Throwable $erro): void {
            error_log('Erro não tratado: ' . $erro);
            encerrarComErro(500);
        });
    }
} else {
    ini_set('display_errors', '1');
}

prepararRespostaHttp();

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => requisicaoHttps(),
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', '2592000');
session_start();

$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', appConfig('db')['host'], appConfig('db')['nome']),
    (string) appConfig('db')['usuario'],
    (string) appConfig('db')['senha'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
// O banco usa o mesmo fuso do PHP: NOW() e date() devolvem a mesma hora local,
// a mesma em que os horários dos eventos são cadastrados.
$pdo->exec("SET time_zone = '" . date('P') . "'");

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        encerrarComErro(400, 'A página ficou aberta por muito tempo ou a solicitação não pôde ser confirmada. Atualize a página e tente novamente.');
    }
}

/** Tempo máximo sem uso antes de a sessão expirar (RNF04). */
function limiteInatividadeSessao(string $perfil, bool $manterConectado): int
{
    if (!$manterConectado) {
        return 2 * 60 * 60;
    }

    // Perfis de gestão alteram dados de outras pessoas: mesmo com "manter
    // conectado", a sessão dura no máximo 12 horas sem uso.
    return $perfil === 'participante' ? 30 * 24 * 60 * 60 : 12 * 60 * 60;
}

/** Impressão da senha atual guardada na sessão: muda quando a senha é trocada. */
function assinaturaSenha(string $senhaHash): string
{
    return hash('sha256', 'eventeen-sessao|' . $senhaHash);
}

/** Cria a sessão do usuário depois de um login ou cadastro bem-sucedido. */
function iniciarSessaoUsuario(array $usuario, bool $manterConectado = false): void
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => (int) $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'perfil' => $usuario['perfil'],
    ];
    $_SESSION['manter_conectado'] = $manterConectado;
    $_SESSION['ultimo_acesso'] = time();
    $_SESSION['senha_assinatura'] = assinaturaSenha((string) $usuario['senha_hash']);

    if ($manterConectado && PHP_SAPI !== 'cli') {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => time() + limiteInatividadeSessao((string) $usuario['perfil'], true),
            'path' => $parametros['path'],
            'secure' => requisicaoHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function encerrarSessao(): void
{
    $_SESSION = [];
    if (PHP_SAPI !== 'cli' && ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parametros['path'],
            'domain' => $parametros['domain'],
            'secure' => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => $parametros['samesite'] ?? 'Lax',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/** Confere o código exigido no cadastro público de organizadores (vazio na configuração = cadastro desativado). */
function codigoOrganizadorValido(string $codigoInformado): bool
{
    $codigoEsperado = trim((string) appConfig('codigo_organizador'));

    return $codigoEsperado !== '' && strlen($codigoInformado) <= 256 && hash_equals($codigoEsperado, trim($codigoInformado));
}

/** Aviso exibido no início dos formulários com campos obrigatórios. */
function renderRequiredFieldsNote(): void
{
    echo '<p class="required-fields-note">Campos marcados com <span class="campo-obrigatorio" aria-hidden="true">*</span> são obrigatórios.</p>';
}

function requireLogin(): array
{
    global $pdo;

    $paginaLogin = applicationRootPrefix() . 'entrar.php';
    if (empty($_SESSION['usuario']['id'])) {
        header('Location: ' . $paginaLogin);
        exit;
    }

    // O perfil é relido a cada requisição para que concessões e revogações
    // feitas pelo administrador tenham efeito imediato (RNF04).
    $consulta = $pdo->prepare('SELECT id, nome, email, perfil, senha_hash FROM usuarios WHERE id = :id LIMIT 1');
    $consulta->execute(['id' => (int) $_SESSION['usuario']['id']]);
    $atual = $consulta->fetch();
    if (!$atual) {
        encerrarSessao();
        header('Location: ' . $paginaLogin);
        exit;
    }

    $agora = time();
    $ultimoAcesso = (int) ($_SESSION['ultimo_acesso'] ?? $agora);
    if ($agora - $ultimoAcesso > limiteInatividadeSessao((string) $atual['perfil'], !empty($_SESSION['manter_conectado']))) {
        encerrarSessao();
        header('Location: ' . $paginaLogin . '?sessao=expirada');
        exit;
    }

    // Se a senha foi trocada (em outro dispositivo ou pela recuperação de
    // senha), as demais sessões abertas deixam de valer.
    $assinatura = assinaturaSenha((string) $atual['senha_hash']);
    if (!isset($_SESSION['senha_assinatura'])) {
        $_SESSION['senha_assinatura'] = $assinatura;
    } elseif (!hash_equals((string) $_SESSION['senha_assinatura'], $assinatura)) {
        encerrarSessao();
        header('Location: ' . $paginaLogin . '?sessao=senha');
        exit;
    }

    $_SESSION['ultimo_acesso'] = $agora;
    $_SESSION['usuario'] = [
        'id' => (int) $atual['id'],
        'nome' => $atual['nome'],
        'email' => $atual['email'],
        'perfil' => $atual['perfil'],
    ];

    return $_SESSION['usuario'];
}

function profileHomePath(?string $perfil): string
{
    return match ($perfil) {
        'organizador' => 'organizador/index.php',
        'administrador' => 'administrador/index.php',
        default => 'inicio-participante.php',
    };
}

function requireRole(string ...$perfisPermitidos): array
{
    $usuario = requireLogin();
    $perfil = (string) ($usuario['perfil'] ?? 'participante');

    if ($perfil !== 'administrador' && !in_array($perfil, $perfisPermitidos, true)) {
        header('Location: ' . applicationRootPrefix() . profileHomePath($perfil));
        exit;
    }

    return $usuario;
}

function organizadorEscopoEventoSql(string $eventoId): string
{
    return "EXISTS (
        SELECT 1 FROM usuarios usuario_escopo
        WHERE usuario_escopo.id = :usuario_id
          AND (
              usuario_escopo.perfil = 'administrador'
              OR EXISTS (
                  SELECT 1 FROM evento_organizadores evento_escopo
                  WHERE evento_escopo.evento_id = {$eventoId}
                    AND evento_escopo.usuario_id = usuario_escopo.id
              )
          )
    )";
}

function displayName(array $usuario): string
{
    $nome = trim((string) ($usuario['nome'] ?? ''));
    return $nome !== '' ? $nome : 'Participante';
}

function initials(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $iniciais = '';
    foreach (array_slice($partes, 0, 2) as $parte) {
        $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8');
    }

    return $iniciais !== '' ? $iniciais : 'EV';
}

function formatDateTime(string $dateTime): string
{
    return date('d/m/Y \\à\\s H\\hi', strtotime($dateTime));
}

function formatPeriodo(string $inicio, string $fim): string
{
    $i = strtotime($inicio);
    $f = strtotime($fim);
    if (date('Y-m-d', $i) === date('Y-m-d', $f)) {
        return date('d/m/Y', $i) . ' · ' . date('H\hi', $i) . '–' . date('H\hi', $f);
    }

    return date('d/m/Y H\hi', $i) . ' até ' . date('d/m/Y H\hi', $f);
}

/**
 * Indica se a página atual pertence à área do participante (pasta participante/ ou atalhos da raiz).
 */
function paginaDaAreaParticipante(): bool
{
    $script = basename(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')));

    return basename(applicationScriptDirectory()) === 'participante'
        || in_array($script, [
            'inicio-participante.php', 'eventos.php', 'detalhes-evento.php', 'minhas-inscricoes.php',
            'cancelar-inscricao.php', 'historico-certificados.php', 'certificado.php',
        ], true);
}

function appHeader(array $usuario): void
{
    $perfil = (string) ($usuario['perfil'] ?? 'participante');
    $prefixo = applicationRootPrefix();
    $areaParticipante = paginaDaAreaParticipante();
    // O logo leva ao início da área exibida; fora dela, ao início do perfil do usuário.
    $paginaInicial = $prefixo . ($areaParticipante ? 'inicio-participante.php' : profileHomePath($perfil));
    $voltarGestao = $perfil !== 'participante'
        ? [$prefixo . profileHomePath($perfil), $perfil === 'administrador' ? 'Voltar à administração' : 'Voltar à organização']
        : null;
    ?>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
    <header class="app-topbar">
        <a class="app-brand" href="<?= h($paginaInicial) ?>" aria-label="Eventeen - página inicial">
            <img class="app-brand__mark" src="<?= h(urlRecurso('img/eventeen-marca.png')) ?>" alt="" width="28" height="32">
            <span class="app-brand__name">Eventeen</span>
        </a>
        <div class="app-topbar__actions">
            <?php if ($voltarGestao !== null): ?>
                <a class="app-topbar__link app-topbar__link--destaque" href="<?= h($voltarGestao[0]) ?>" title="<?= h($voltarGestao[1]) ?>">
                    <span aria-hidden="true">←</span><span class="app-topbar__text"><?= h($voltarGestao[1]) ?></span>
                </a>
            <?php endif; ?>
            <span class="app-pill" title="<?= h(displayName($usuario)) ?>" aria-hidden="true"><?= h(initials(displayName($usuario))) ?></span>
            <a class="app-topbar__link" href="<?= h($prefixo) ?>configuracoes-conta.php" title="Minha conta">
                <span aria-hidden="true">⚙</span><span class="app-topbar__text">Minha conta</span>
            </a>
            <form class="app-logout-form" method="post" action="<?= h($prefixo) ?>sair.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <button class="app-topbar__link app-logout-button" type="submit" title="Sair da conta">
                    <span aria-hidden="true">↪</span><span class="app-topbar__text">Sair</span>
                </button>
            </form>
        </div>
    </header>
    <?php
}

function appNavigation(string $active): void
{
    $items = [
        'inicio' => ['inicio-participante.php', '⌂', 'Início'],
        'eventos' => ['eventos.php', '🗓️', 'Eventos'],
        'inscricoes' => ['minhas-inscricoes.php', '🧾', 'Inscrições'],
        'historico' => ['historico-certificados.php', '🎓', 'Histórico'],
    ];
    ?>
    <nav class="app-bottom-nav" aria-label="Navegação principal">
        <?php foreach ($items as $key => [$url, $icon, $label]): ?>
            <a class="app-nav-item<?= $active === $key ? ' app-nav-item--active' : '' ?>" href="<?= h($url) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>>
                <span class="app-nav-item__icon" aria-hidden="true"><?= h($icon) ?></span>
                <span><?= h($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function renderNotice(?string $message, bool $isError = false): void
{
    if ($message === null || $message === '') {
        return;
    }
    ?>
    <p class="app-notice<?= $isError ? ' app-notice--error' : '' ?>" role="<?= $isError ? 'alert' : 'status' ?>"><?= h($message) ?></p>
    <?php
}
