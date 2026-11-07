<?php

namespace Piccolo\Http\Api;

/**
 * Convierte filas de la base al formato que devuelve la API (nombres en camelCase,
 * números como números y URLs absolutas para las imágenes).
 */
final class Formato
{
    public static function urlPublica(string $ruta): string
    {
        if (preg_match('#^https?://#i', $ruta)) {
            return $ruta;
        }

        return \piccolo_public_base_url() . ltrim($ruta, '/');
    }

    public static function producto(array $fila, ?array $disponibilidad = null): array
    {
        $foto = trim((string) ($fila['foto'] ?? ''));

        return [
            'id' => (int) $fila['ID'],
            'nombre' => (string) $fila['nombre'],
            'ingredientes' => (string) ($fila['ingredientes'] ?? ''),
            'precio' => (float) $fila['precio'],
            'categoria' => (string) ($fila['categoria'] ?? ''),
            'imagen' => self::urlPublica($foto !== '' ? 'img/menu/' . rawurlencode($foto) : 'img/placeholders/producto.svg'),
            'disponibles' => (int) ($disponibilidad['unidades_disponibles'] ?? 0),
        ];
    }

    /** @param array<string, array> $disponibilidad resultado de obtenerDisponibilidadMenu() */
    public static function disponibilidad(array $disponibilidad): array
    {
        return array_values(array_map(static fn($fila) => [
            'productoId' => (int) $fila['menu_id'],
            'disponibles' => (int) $fila['unidades_disponibles'],
        ], $disponibilidad));
    }

    public static function pedido(array $fila): array
    {
        return [
            'id' => (int) $fila['ID'],
            'fecha' => $fila['fecha'] ?? null,
            'estado' => (string) ($fila['estado'] ?? ''),
            'pagado' => \piccolo_interpretar_estado_pago_para_presentacion($fila['esta_pago'] ?? '')['es_pagado'],
            'total' => (float) ($fila['total'] ?? 0),
            'metodoPago' => (string) ($fila['metodo_pago'] ?? ''),
            'tipoEntrega' => (string) ($fila['tipo_entrega'] ?? ''),
            'direccion' => (string) ($fila['direccion'] ?? ''),
            'nota' => (string) ($fila['nota'] ?? ''),
            'items' => array_map(static fn($detalle) => [
                'nombre' => (string) $detalle['nombre'],
                'precio' => (float) $detalle['precio'],
                'cantidad' => (int) $detalle['cantidad'],
            ], $fila['detalles'] ?? []),
        ];
    }

    public static function cliente(array $fila): array
    {
        $email = trim((string) ($fila['email'] ?? ''));

        return [
            'id' => (int) $fila['ID'],
            'nombre' => (string) $fila['nombre'],
            'telefono' => (string) $fila['telefono'],
            'email' => $email === '' ? null : $email,
            'puntos' => (int) ($fila['puntos'] ?? 0),
            'fechaRegistro' => $fila['fecha_registro'] ?? null,
            // Las cuentas viejas pueden no tener email; el sitio le pide al cliente que lo cargue.
            'emailPendiente' => $email === '',
        ];
    }
}
