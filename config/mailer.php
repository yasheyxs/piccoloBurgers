<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

/**

 * @throws RuntimeException si faltan las variables obligatorias.
 */
function crearMailer(): PHPMailer
{
    $host = getenv('MAILER_HOST') ?: '';
    $username = getenv('MAILER_USERNAME') ?: '';
    $password = getenv('MAILER_PASSWORD') ?: '';

    if ($host === '' || $username === '' || $password === '') {
        throw new RuntimeException(
            'Faltan las variables MAILER_HOST, MAILER_USERNAME o MAILER_PASSWORD para configurar el envío de correos.'
        );
    }

    $fromAddress = getenv('MAILER_FROM_ADDRESS') ?: $username;
    $fromName = getenv('MAILER_FROM_NAME') ?: 'Piccolo Burgers';
    $port = (int) (getenv('MAILER_PORT') ?: 587);
    $encryption = strtolower((string) getenv('MAILER_ENCRYPTION')) ?: 'tls';

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