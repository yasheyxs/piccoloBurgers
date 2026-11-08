<?php
/** Pedir un enlace para crear una contraseña nueva. No requiere sesión. */
require_once __DIR__ . '/../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContrasenaPanelController;
use Piccolo\Http\Api\ApiException;

$aviso = null;
$errores = [];
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = (string) ($_POST['correo'] ?? '');
    if (!piccolo_csrf_valido()) {
        $aviso = ['tipo' => 'warning', 'mensaje' => 'La página venció. Probá de nuevo.'];
    } else {
        try {
            (new ContrasenaPanelController($conexion))->solicitar($correo, static function (string $correo, string $usuario, string $enlace): bool {
                try {
                    require_once piccolo_path('config/mailer.php');
                    $mailer = crearMailer();
                    $mailer->addAddress($correo, $usuario);
                    $mailer->isHTML(true);
                    $mailer->Subject = 'Recuperación de contraseña - Piccolo Burgers';
                    $texto = "Hola $usuario,\n\nRecibimos un pedido para crear una contraseña nueva del panel de Piccolo Burgers.\n"
                        . "Entrá a este enlace en los próximos 30 minutos:\n$enlace\n\nSi no lo pediste, ignorá este mensaje.";
                    $mailer->Body = nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));
                    $mailer->AltBody = $texto;
                    $mailer->send();

                    return true;
                } catch (Throwable $error) {
                    error_log('Error al enviar el correo de recuperación: ' . $error->getMessage());

                    return false;
                }
            }, piccolo_admin_base_url() . 'password/reset_password.php');
            $aviso = ['tipo' => 'success', 'mensaje' => ContrasenaPanelController::MENSAJE_ENVIADO];
            $correo = '';
        } catch (ApiException $error) {
            $errores = $error->errores();
            $aviso = ['tipo' => 'danger', 'mensaje' => $errores ? implode(' ', $errores) : $error->getMessage()];
        }
    }
}

piccolo_admin_pagina_publica('contrasena/recuperar', ['correo' => $correo, 'errores' => $errores, 'aviso' => $aviso]);
