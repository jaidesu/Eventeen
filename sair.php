<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    encerrarComErro(405);
}

validateCsrfToken();

encerrarSessao();

header('Location: ' . applicationRootPrefix() . 'index.php');
exit;
