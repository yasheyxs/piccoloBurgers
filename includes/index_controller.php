<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/email_requirement.php';
require_once __DIR__ . '/../../includes/email_requirement.php';

enforceEmailRequirement();

$conexion = requireConnection($conexion ?? null);

$sentencia = $conexion->prepare("SELECT * FROM tbl_banners ORDER BY id DESC LIMIT 1");
$sentencia->execute();
$lista_banners = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$sentencia = $conexion->prepare("SELECT * FROM tbl_testimonios ORDER BY id DESC");
$sentencia->execute();
$lista_testimonios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = filter_var($_POST['nombre'] ?? '', FILTER_SANITIZE_STRING);
    $correo = filter_var($_POST['correo'] ?? '', FILTER_VALIDATE_EMAIL);
    $mensaje = filter_var($_POST['mensaje'] ?? '', FILTER_SANITIZE_STRING);

    if ($nombre && $correo && $mensaje) {
        $sql = "INSERT INTO tbl_comentarios (nombre, correo, mensaje) VALUES (:nombre, :correo, :mensaje)";
        $resultado = $conexion->prepare($sql);
        $resultado->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $resultado->bindParam(':correo', $correo, PDO::PARAM_STR);
        $resultado->bindParam(':mensaje', $mensaje, PDO::PARAM_STR);
        $resultado->execute();

        $_SESSION['toast'] = [
            'mensaje' => '¡Gracias por tu comentario!',
            'tipo' => 'success',
        ];
    } else {
        $_SESSION['toast'] = [
            'mensaje' => 'Hubo un error al enviar el formulario.',
            'tipo' => 'danger',
        ];
    }

    header('Location: index.php#contacto');
    exit;
}