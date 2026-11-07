<?php

declare(strict_types=1);

namespace Piccolo\Tests\Unit;

use PDO;
use PedidoException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Los datos inválidos se rechazan antes de tocar la base: por eso alcanza con una conexión vacía.
 */
final class ValidacionPedidoTest extends TestCase
{
    private const PEDIDO_VALIDO = [
        'nombre' => 'Ana',
        'telefono' => '3511234567',
        'codigo_pais' => '54',
        'metodo_pago' => 'Efectivo',
        'tipo_entrega' => 'Retiro',
        'items' => [['id' => 1, 'cantidad' => 1]],
    ];

    public static function pedidosInvalidos(): array
    {
        return [
            'sin nombre' => [['nombre' => ''], 'Faltan datos obligatorios.'],
            'sin teléfono' => [['telefono' => ''], 'Faltan datos obligatorios.'],
            'email inválido' => [['email' => 'ana@'], 'Ingresá un email válido o dejalo en blanco.'],
            'teléfono inválido' => [['telefono' => '123'], 'Ingresá un número de teléfono válido.'],
            'carrito vacío' => [['items' => []], 'El carrito está vacío.'],
            'medio de pago inexistente' => [['metodo_pago' => 'Bitcoin'], 'Por favor, seleccioná método de pago y tipo de entrega.'],
            'delivery sin dirección' => [['tipo_entrega' => 'Delivery'], 'Debes ingresar una dirección para el envío.'],
            'cantidad en cero' => [['items' => [['id' => 1, 'cantidad' => 0]]], 'Hay un producto inválido en el carrito.'],
            'producto sin id' => [['items' => [['cantidad' => 2]]], 'Hay un producto inválido en el carrito.'],
        ];
    }

    #[DataProvider('pedidosInvalidos')]
    public function testRechazaLosDatosInvalidos(array $cambios, string $mensaje): void
    {
        $this->expectException(PedidoException::class);
        $this->expectExceptionMessage($mensaje);

        crearPedido(new PDO('sqlite::memory:'), array_merge(self::PEDIDO_VALIDO, $cambios));
    }
}
