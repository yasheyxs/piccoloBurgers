<?php

namespace Piccolo\Http\Api;

/**
 * Sesión del cliente. Se comparte con el sitio actual (misma cookie), así que
 * iniciar sesión en uno también la inicia en el otro.
 */
final class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $esHttps = \piccolo_detect_scheme() === 'https';
        // Si el frontend vive en otro dominio, la cookie necesita SameSite=None (y HTTPS).
        $sameSite = (string) (getenv('SESSION_SAMESITE') ?: 'Lax');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $esHttps || strcasecmp($sameSite, 'None') === 0,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);
        session_start();
    }

    /** @return array{id:int, nombre:string, telefono:string, email:?string}|null */
    public static function cliente(): ?array
    {
        self::iniciar();
        $cliente = $_SESSION['cliente'] ?? null;

        return is_array($cliente) && !empty($cliente['id']) ? $cliente : null;
    }

    /** @return array{id:int, nombre:string, telefono:string, email:?string} */
    public static function clienteObligatorio(): array
    {
        $cliente = self::cliente();
        if ($cliente === null) {
            throw ApiException::noAutenticado();
        }

        return $cliente;
    }

    public static function guardarCliente(array $cliente): void
    {
        self::iniciar();
        session_regenerate_id(true);
        $_SESSION['cliente'] = [
            'id' => (int) $cliente['ID'],
            'nombre' => $cliente['nombre'],
            'telefono' => $cliente['telefono'],
            'email' => $cliente['email'],
        ];
    }

    public static function cerrar(): void
    {
        self::iniciar();
        unset($_SESSION['cliente'], $_SESSION['email_obligatorio_mensaje']);
        session_regenerate_id(true);
    }
}
