<?php
declare(strict_types=1);

/*
 * Base da aplicação: configuração, endereços e escape de HTML.
 * Não depende do banco nem da sessão, para que as páginas de erro funcionem
 * mesmo quando o banco estiver fora do ar. Carregado por config.php.
 */

/**
 * Configuração da aplicação.
 *
 * Os valores abaixo servem para o ambiente local (XAMPP). Em um servidor,
 * copie config.exemplo.php para config.local.php (arquivo fora do Git) e
 * informe ali o banco, o endereço público e o envio de e-mails.
 */
function appConfig(?string $chave = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = [
            'ambiente' => null,
            'url_base' => '',
            'forcar_https' => false,
            'pasta_dados' => dirname(__DIR__) . '/storage',
            'fuso_horario' => 'America/Sao_Paulo',
            // Código exigido no cadastro público de organizadores. Em produção, troque-o em config.local.php.
            'codigo_organizador' => 'FTC_OUR_26',
            'db' => ['host' => '127.0.0.1', 'nome' => 'eventeen', 'usuario' => 'root', 'senha' => ''],
            'email' => [
                'transporte' => 'arquivo',
                'remetente' => 'nao-responda@eventeen.local',
                'nome_remetente' => 'Eventeen',
                'smtp' => ['host' => '', 'porta' => 587, 'seguranca' => 'tls', 'usuario' => '', 'senha' => '', 'tempo_limite' => 15],
            ],
        ];
        $arquivoLocal = dirname(__DIR__) . '/config.local.php';
        if (is_file($arquivoLocal)) {
            $local = require $arquivoLocal;
            if (is_array($local)) {
                $config = array_replace_recursive($config, $local);
            }
        }
        foreach (['host' => 'EVENTEEN_DB_HOST', 'nome' => 'EVENTEEN_DB_NOME', 'usuario' => 'EVENTEEN_DB_USUARIO', 'senha' => 'EVENTEEN_DB_SENHA'] as $campo => $variavel) {
            $valor = getenv($variavel);
            if ($valor !== false) {
                $config['db'][$campo] = $valor;
            }
        }
        if (getenv('EVENTEEN_AMBIENTE') !== false) {
            $config['ambiente'] = getenv('EVENTEEN_AMBIENTE');
        }
        if (!in_array($config['ambiente'], ['desenvolvimento', 'producao'], true)) {
            // Sem configuração explícita, só se considera desenvolvimento quando
            // o acesso vem da própria máquina; qualquer outro acesso é tratado
            // como produção (erros ocultos).
            $local = PHP_SAPI === 'cli' || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
            $config['ambiente'] = $local ? 'desenvolvimento' : 'producao';
        }
    }

    return $chave === null ? $config : ($config[$chave] ?? null);
}

if (!@date_default_timezone_set((string) appConfig('fuso_horario'))) {
    date_default_timezone_set('America/Sao_Paulo');
}

function appEmProducao(): bool
{
    return appConfig('ambiente') === 'producao';
}

function appPastaDados(string $subpasta): string
{
    $pasta = rtrim((string) appConfig('pasta_dados'), '/\\') . '/' . $subpasta;
    if (!is_dir($pasta)) {
        @mkdir($pasta, 0770, true);
    }

    return $pasta;
}

function requisicaoHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' && appConfig('forcar_https'));
}

function h(string|int|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function applicationBaseUrl(): string
{
    // Em produção, links enviados por e-mail ou impressos em certificados usam
    // o endereço configurado, e não o cabeçalho Host da requisição.
    $urlConfigurada = trim((string) appConfig('url_base'));
    if ($urlConfigurada !== '') {
        return rtrim($urlConfigurada, '/');
    }

    $https = requisicaoHttps();
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.\-]+(?::\d{1,5})?$/', $host)) {
        $host = 'localhost';
    }

    return ($https ? 'https' : 'http') . '://' . $host . applicationBasePath();
}

function applicationRootPrefix(): string
{
    $scriptDirectory = applicationScriptDirectory();
    $scriptDirectory = rtrim($scriptDirectory, '/');
    $ultimaBarra = strrpos($scriptDirectory, '/');
    $pastaAtual = $ultimaBarra === false ? $scriptDirectory : substr($scriptDirectory, $ultimaBarra + 1);
    return in_array($pastaAtual, ['participante', 'organizador', 'administrador'], true) ? '../' : '';
}

function applicationBasePath(): string
{
    $scriptDirectory = applicationScriptDirectory();
    $normalizedDirectory = rtrim($scriptDirectory, '/');
    $ultimaBarra = strrpos($normalizedDirectory, '/');
    $pastaAtual = $ultimaBarra === false ? $normalizedDirectory : substr($normalizedDirectory, $ultimaBarra + 1);

    if (in_array($pastaAtual, ['participante', 'organizador', 'administrador'], true)) {
        $scriptDirectory = $ultimaBarra === false ? '' : substr($normalizedDirectory, 0, $ultimaBarra);
    }

    return $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
}

function applicationScriptDirectory(): string
{
    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $ultimaBarra = strrpos($scriptPath, '/');

    return $ultimaBarra === false ? '' : substr($scriptPath, 0, $ultimaBarra);
}
