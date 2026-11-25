<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/includes/autenticacao.php';

if (!empty($_SESSION['usuario'])) {
    header('Location: ' . profileHomePath((string) ($_SESSION['usuario']['perfil'] ?? 'participante')));
    exit;
}

$erro = null;
$aviso = match ($_GET['sessao'] ?? $_GET['senha'] ?? '') {
    'expirada' => 'Sua sessão expirou por inatividade. Entre novamente.',
    'senha' => 'Sua senha foi alterada. Entre novamente com a nova senha.',
    'redefinida' => 'Senha redefinida. Entre com a nova senha.',
    default => null,
};
$emailInformado = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $aviso = null;
    $emailInformado = trim((string) ($_POST['email'] ?? ''));
    $email = filter_var(normalizarEmailAcesso($emailInformado), FILTER_VALIDATE_EMAIL);
    $senha = (string) ($_POST['senha'] ?? '');

    if (!$email || $senha === '') {
        $erro = 'Informe um e-mail válido e sua senha.';
    } else {
        try {
            $usuario = autenticarUsuario($pdo, $email, $senha, ipCliente());
            iniciarSessaoUsuario($usuario, isset($_POST['manter-conectado']));
            header('Location: ' . profileHomePath((string) $usuario['perfil']));
            exit;
        } catch (DomainException $exception) {
            $erro = $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderHeadPagina('Entrar', ['indexar' => true, 'canonico' => '/entrar.php', 'descricao' => 'Entre no Eventeen para se inscrever em eventos da Fatec Ourinhos, acompanhar suas inscrições e emitir certificados.']); ?>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="auth-page">
        <a class="marca" href="index.php" aria-label="Eventeen - página inicial">
            <img class="logo" src="img/logo-eventeen-web.png" alt="Eventeen" width="190" height="68">
        </a>

        <section class="auth-card" aria-labelledby="titulo">
            <header class="auth-header">
                <p class="eyebrow">Fatec Ourinhos</p>
                <h1 id="titulo">Bem-vindo ao Eventeen</h1>
                <p class="subtitulo">Do convite ao certificado.</p>
            </header>

            <?php if ($erro !== null): ?>
                <p class="form-notice form-notice--error" role="alert"><?= h($erro) ?></p>
            <?php elseif ($aviso !== null): ?>
                <p class="form-notice form-notice--success" role="status"><?= h($aviso) ?></p>
            <?php endif; ?>

            <form class="auth-form" action="entrar.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <?php renderRequiredFieldsNote(); ?>
                <div class="campo">
                    <label for="email">E-mail <span class="campo-obrigatorio" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" maxlength="190" autocomplete="email" placeholder="seuemail@exemplo.com" value="<?= h($emailInformado) ?>" required>
                </div>

                <div class="campo">
                    <label for="senha">Senha <span class="campo-obrigatorio" aria-hidden="true">*</span></label>
                    <input type="password" id="senha" name="senha" maxlength="<?= SENHA_LOGIN_TAMANHO_MAXIMO ?>" autocomplete="current-password" placeholder="Digite sua senha" required>
                    <a class="link-auxiliar" href="esqueci-senha.php">Esqueci minha senha</a>
                </div>

                <label class="checkbox-linha" for="manter-conectado">
                    <input type="checkbox" id="manter-conectado" name="manter-conectado">
                    <span>Manter conectado</span>
                </label>

                <button type="submit" class="botao">Entrar</button>
            </form>

            <p class="rodape">Ainda não tem conta? <a href="cadastro.php">Criar conta</a></p>
            <p class="rodape">Recebeu um certificado? <a href="validar-certificado.php">Validar certificado</a></p>
            <?php renderInstalarApp(); ?>
        </section>

        <footer class="assinatura">Fatec Ourinhos</footer>
    </main>
</body>
</html>