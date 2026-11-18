<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/gestao.php';
require_once __DIR__ . '/../includes/certificados.php';

// Certificados dos participantes, acessados pela organização do evento.
$usuario = requireRole('organizador');
$organizadorId = (int) $usuario['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $eventoId = filter_var($_POST['evento_id'] ?? null, FILTER_VALIDATE_INT);
    $atividadeId = filter_var($_POST['atividade_id'] ?? null, FILTER_VALIDATE_INT);
    $participanteId = filter_var($_POST['participante_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$eventoId || !$atividadeId || !$participanteId || min($eventoId, $atividadeId, $participanteId) < 1) {
        header('Location: presencas.php');
        exit;
    }
    try {
        $codigo = emitirCertificadoPeloOrganizador($pdo, $organizadorId, $eventoId, $atividadeId, $participanteId);
    } catch (DomainException $exception) {
        $_SESSION['aviso_presencas'] = $exception->getMessage();
        header('Location: presencas.php?evento_id=' . $eventoId . '&atividade_id=' . $atividadeId);
        exit;
    }
    header('Location: certificado.php?codigo=' . rawurlencode($codigo));
    exit;
}

$codigoRecebido = $_GET['codigo'] ?? '';
$certificado = is_string($codigoRecebido) ? buscarCertificadoPorCodigo($pdo, mb_substr($codigoRecebido, 0, 24, 'UTF-8')) : null;
if ($certificado === null || !organizadorGerenciaEvento($pdo, $organizadorId, (int) $certificado['evento_id'])) {
    http_response_code(404);
    exit('Certificado não encontrado ou você não tem permissão para acessá-lo.');
}

$pdf = gerarPdfCertificado($certificado, applicationBaseUrl() . '/validar-certificado.php');
$nomeArquivo = 'certificado-' . strtolower((string) $certificado['codigo_validacao']) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
header('Content-Length: ' . strlen($pdf));
header('X-Content-Type-Options: nosniff');
echo $pdf;
