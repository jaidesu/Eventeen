<?php
declare(strict_types=1);

/*
 * Página de erro do Eventeen (404, 403, 500...).
 * O servidor web a usa como ErrorDocument (veja o .htaccess da raiz) e ela também
 * pode ser aberta diretamente com ?codigo=404. Não usa o banco nem a sessão, para
 * funcionar mesmo quando o banco estiver fora do ar.
 */
require __DIR__ . '/includes/pagina.php';

// O Apache informa o erro original em REDIRECT_STATUS; sem ele, vale o ?codigo= (padrão: 404).
$status = (int) ($_SERVER['REDIRECT_STATUS'] ?? 0);
$codigoInformado = $_GET['codigo'] ?? '';
$codigo = $status >= 400 ? $status : (is_string($codigoInformado) && ctype_digit($codigoInformado) ? (int) $codigoInformado : 404);

prepararRespostaHttp();
if ($codigo === 503 && !headers_sent()) {
    header('Retry-After: 600');
}

encerrarComErro(codigoErroHttpValido($codigo));
