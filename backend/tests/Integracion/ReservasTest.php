<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use RuntimeException;

/** El carrito reserva insumos: dos clientes no pueden quedarse con el mismo stock. */
final class ReservasTest extends BaseDePruebas
{
    private string $sesionA;
    private string $sesionB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sesionA = 'prueba-a-' . bin2hex(random_bytes(6));
        $this->sesionB = 'prueba-b-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (isset($this->conexion)) {
            limpiarReservasSesion($this->conexion, $this->sesionA);
            limpiarReservasSesion($this->conexion, $this->sesionB);
        }
        parent::tearDown();
    }

    public function testReservaMientrasHayaStock(): void
    {
        $producto = $this->crearProductoConStock(1000, 3)['producto'];

        $resultado = establecerReservaProducto($this->conexion, $this->sesionA, $producto, 3);

        $this->assertSame(3, $resultado['reservas'][(string) $producto]['cantidad']);
    }

    public function testOtroClienteNoPuedeReservarLoYaReservado(): void
    {
        $producto = $this->crearProductoConStock(1000, 3)['producto'];
        establecerReservaProducto($this->conexion, $this->sesionA, $producto, 2);

        // Queda 1 disponible: la otra sesión puede tomar 1, pero no 2
        establecerReservaProducto($this->conexion, $this->sesionB, $producto, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sin stock disponible');
        establecerReservaProducto($this->conexion, $this->sesionB, $producto, 2);
    }

    public function testAlVaciarElCarritoSeLiberaElStock(): void
    {
        $producto = $this->crearProductoConStock(1000, 2)['producto'];
        establecerReservaProducto($this->conexion, $this->sesionA, $producto, 2);

        limpiarReservasSesion($this->conexion, $this->sesionA);
        $resultado = establecerReservaProducto($this->conexion, $this->sesionB, $producto, 2);

        $this->assertSame(2, $resultado['reservas'][(string) $producto]['cantidad']);
    }
}
