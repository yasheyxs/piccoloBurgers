<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../src/Core/bootstrap.php';

/**
 * @throws RuntimeException si faltan las variables obligatorias.
 */
function crearMailer(): PHPMailer
{
    static $envCargado = false;

    if (!$envCargado) {
        piccolo_load_environment();
        $envCargado = true;
    }

    $host = piccolo_env('MAILER_HOST') ?: '';
    $username = piccolo_env('MAILER_USERNAME') ?: '';
    $password = piccolo_env('MAILER_PASSWORD') ?: '';

    if ($host === '' || $username === '' || $password === '') {
        throw new RuntimeException(
            'Faltan las variables MAILER_HOST, MAILER_USERNAME o MAILER_PASSWORD para configurar el envío de correos.'
        );
    }

    $fromAddress = piccolo_env('MAILER_FROM_ADDRESS') ?: $username;
    $fromName = piccolo_env('MAILER_FROM_NAME') ?: 'Piccolo Burgers';
    $port = (int) (piccolo_env('MAILER_PORT') ?: 587);
    $encryption = strtolower((string) piccolo_env('MAILER_ENCRYPTION')) ?: 'tls';

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $host;
    $mailer->SMTPAuth = true;
    $mailer->Username = $username;
    $mailer->Password = $password;
    $mailer->Port = $port;
    $mailer->CharSet = 'UTF-8';

    switch ($encryption) {
        case 'ssl':
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            break;
        case 'none':
        case '':
            $mailer->SMTPSecure = false;
            $mailer->SMTPAutoTLS = false;
            break;
        case 'tls':
        default:
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            break;
    }

    $mailer->setFrom($fromAddress, $fromName);

    return $mailer;
}
