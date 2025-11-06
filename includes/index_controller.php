<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/email_requirement.php';


if (!function_exists('piccolo_sanitizar_texto_basico')) {
    function piccolo_sanitizar_texto_basico($valor, int $longitudMaxima = 255): string
    {
        $valor = is_string($valor) ? trim($valor) : '';

        if ($valor === '') {
            return '';
        }

        $valor = strip_tags($valor);

        if (function_exists('mb_substr')) {
            $valor = mb_substr($valor, 0, $longitudMaxima);
        } else {
            $valor = substr($valor, 0, $longitudMaxima);
        }

        return $valor;
    }
}

enforceEmailRequirement();

$conexion = requireConnection($conexion ?? null);

$sentencia = $conexion->prepare("SELECT * FROM tbl_banners ORDER BY id DESC LIMIT 1");
$sentencia->execute();
$lista_banners = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$sentencia = $conexion->prepare("SELECT * FROM tbl_testimonios ORDER BY id DESC");
$sentencia->execute();
$lista_testimonios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = piccolo_sanitizar_texto_basico($_POST['nombre'] ?? '', 120);
    $correo = filter_var($_POST['correo'] ?? '', FILTER_VALIDATE_EMAIL);
    $mensaje = piccolo_sanitizar_texto_basico($_POST['mensaje'] ?? '', 600);

    if ($nombre !== '' && $correo && $mensaje !== '') {
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
