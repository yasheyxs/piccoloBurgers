<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Carga las variables de entorno definidas en un archivo de texto simple.
 */
function cargarVariablesDeEntorno(string $rutaEnv): void
{
    if (!is_readable($rutaEnv)) {
        return;
    }

    $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);

        if ($linea === '' || $linea[0] === '#') {
            continue;
        }

        $delimitador = strpos($linea, '=');
        if ($delimitador === false) {
            continue;
        }

        $nombre = trim(substr($linea, 0, $delimitador));
        if ($nombre === '') {
            continue;
        }

        $valor = trim(substr($linea, $delimitador + 1));

        if ($valor !== '') {
            $primerCaracter = $valor[0];
            $ultimoCaracter = substr($valor, -1);

            if (($primerCaracter === "\"" || $primerCaracter === "'") && $ultimoCaracter === $primerCaracter) {
                $valor = substr($valor, 1, -1);
            }
        }

        if (
            array_key_exists($nombre, $_ENV) ||
            array_key_exists($nombre, $_SERVER) ||
            getenv($nombre) !== false
        ) {
            continue;
        }

        putenv("$nombre=$valor");
        $_ENV[$nombre] = $valor;
        $_SERVER[$nombre] = $valor;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

/**

 * @throws RuntimeException si faltan las variables obligatorias.
 */
function crearMailer(): PHPMailer
{
    static $envCargado = false;

    if (!$envCargado) {
        cargarVariablesDeEntorno(__DIR__ . '/../.env');
        $envCargado = true;
    }

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
