<?php

/**
 * Límite de intentos fallidos de inicio de sesión, para frenar a quien prueba contraseñas.
 *
 * Se cuenta por tipo de cuenta + usuario + IP: 5 intentos fallidos dentro de 15 minutos
 * bloquean ese usuario desde esa IP durante 15 minutos. Un inicio de sesión correcto
 * borra el contador.
 */

const PICCOLO_LOGIN_MAX_INTENTOS = 5;
const PICCOLO_LOGIN_MINUTOS_BLOQUEO = 15;

if (!function_exists('piccoloLoginClave')) {
    function piccoloLoginClave(string $tipo, string $identificador): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return hash('sha256', $tipo . '|' . mb_strtolower(trim($identificador)) . '|' . $ip);
    }
}

if (!function_exists('piccoloLoginMinutosBloqueado')) {
    /** Minutos que faltan para poder volver a intentar, o 0 si no está bloqueado. */
    function piccoloLoginMinutosBloqueado(PDO $conexion, string $tipo, string $identificador): int
    {
        $stmt = $conexion->prepare(
            'SELECT CEIL(TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) / 60)
             FROM tbl_intentos_login WHERE clave = ? AND bloqueado_hasta > NOW()'
        );
        $stmt->execute([piccoloLoginClave($tipo, $identificador)]);

        return max(0, (int) $stmt->fetchColumn());
    }
}

if (!function_exists('piccoloLoginRegistrarFallo')) {
    function piccoloLoginRegistrarFallo(PDO $conexion, string $tipo, string $identificador): void
    {
        $clave = piccoloLoginClave($tipo, $identificador);
        $ventana = PICCOLO_LOGIN_MINUTOS_BLOQUEO;

        // Si el primer fallo es viejo, el contador vuelve a empezar
        $conexion->prepare(
            "INSERT INTO tbl_intentos_login (clave, intentos, primer_intento) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
               intentos = IF(primer_intento < NOW() - INTERVAL $ventana MINUTE, 1, intentos + 1),
               primer_intento = IF(primer_intento < NOW() - INTERVAL $ventana MINUTE, NOW(), primer_intento)"
        )->execute([$clave]);

        $conexion->prepare(
            "UPDATE tbl_intentos_login SET bloqueado_hasta = NOW() + INTERVAL $ventana MINUTE
             WHERE clave = ? AND intentos >= ?"
        )->execute([$clave, PICCOLO_LOGIN_MAX_INTENTOS]);
    }
}

if (!function_exists('piccoloLoginRegistrarExito')) {
    function piccoloLoginRegistrarExito(PDO $conexion, string $tipo, string $identificador): void
    {
        $conexion->prepare('DELETE FROM tbl_intentos_login WHERE clave = ?')
            ->execute([piccoloLoginClave($tipo, $identificador)]);
    }
}
