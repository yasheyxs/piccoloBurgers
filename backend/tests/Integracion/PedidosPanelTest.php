<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\PedidosPanelController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PedidosPanelRepositorio;

final class PedidosPanelTest extends BaseDePruebas
{
    private PedidosPanelController $pedidos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pedidos = new PedidosPanelController(new PedidosPanelRepositorio($this->conexion), $this->conexion);
        $this->conexion->exec('UPDATE tbl_configuracion_puntos SET minimo_puntos = 50, valor_punto = 20, maximo_porcentaje = 0.25 WHERE id = 1');
    }

    /** @return array{pedido: int, insumo: int} pedido de 2 unidades de un producto que usa 1 insumo por unidad (stock 10) */
    private function pedido(string $entrega = 'Retiro', ?int $cliente = null, bool $usarPuntos = false): array
    {
        ['producto' => $producto, 'insumo' => $insumo] = $this->crearProductoConStock(10000, 10);
        $resultado = crearPedido($this->conexion, [
            'nombre' => 'Prueba', 'telefono' => '3519990202', 'codigo_pais' => '54',
            'metodo_pago' => 'Efectivo', 'tipo_entrega' => $entrega, 'direccion' => $entrega === 'Delivery' ? 'Calle 123' : '',
            'usar_puntos' => $usarPuntos, 'items' => [['id' => $producto, 'cantidad' => 2]],
        ], $cliente);
        $this->anotar('tbl_pedidos', $resultado['pedido_id']);

        return ['pedido' => $resultado['pedido_id'], 'insumo' => $insumo];
    }

    private function stock(int $insumo): float
    {
        return (float) $this->conexion->query("SELECT cantidad FROM tbl_materias_primas WHERE ID = $insumo")->fetchColumn();
    }

    private function rechaza(callable $accion, int $status): void
    {
        try {
            $accion();
            $this->fail("Debería responder $status");
        } catch (ApiException $error) {
            $this->assertSame($status, $error->status(), $error->getMessage());
        }
    }

    public function testListoDescuentaElStockUnaSolaVez(): void
    {
        ['pedido' => $pedido, 'insumo' => $insumo] = $this->pedido();

        $resultado = $this->pedidos->actualizar($pedido, 'Listo', null, 'empleado');
        $this->assertSame(['Listo', true], [$resultado['estado'], $resultado['pagado']], 'al estar listo queda pagado');
        $this->assertSame(8.0, $this->stock($insumo));

        $this->pedidos->actualizar($pedido, 'Listo', null, 'empleado'); // repetido: no hace nada
        $this->pedidos->actualizar($pedido, 'Entregado', null, 'admin');
        $this->assertSame(8.0, $this->stock($insumo), 'ni repetir Listo ni pasar a Entregado vuelven a descontar');
    }

    public function testElRecorridoDelDelivery(): void
    {
        ['pedido' => $retiro] = $this->pedido();
        $this->rechaza(fn() => $this->pedidos->actualizar($retiro, 'En camino', null, 'empleado'), 409);

        ['pedido' => $pedido, 'insumo' => $insumo] = $this->pedido('Delivery');
        $this->rechaza(fn() => $this->pedidos->actualizar($pedido, 'En camino', null, 'delivery'), 403);
        $this->pedidos->actualizar($pedido, 'En camino', null, 'empleado');
        $this->assertSame(10.0, $this->stock($insumo), 'en camino todavía no descuenta');
        $this->assertSame([$pedido], array_column(array_filter($this->pedidos->delivery(), fn($p) => $p['id'] === $pedido), 'id'));

        $this->rechaza(fn() => $this->pedidos->actualizar($pedido, 'Listo', null, 'empleado'), 403);
        $this->rechaza(fn() => $this->pedidos->actualizar($pedido, 'Cancelado', null, 'delivery'), 403);
        $this->pedidos->actualizar($pedido, 'Listo', null, 'delivery');
        $this->assertSame(8.0, $this->stock($insumo));
    }

    public function testCancelarDevuelveLosPuntos(): void
    {
        $cliente = $this->insertar('tbl_clientes', ['nombre' => 'Prueba', 'telefono' => '+543519990303', 'email' => 'cancela@ejemplo.test', 'password' => 'x', 'puntos' => 1000]);
        ['pedido' => $pedido, 'insumo' => $insumo] = $this->pedido('Retiro', $cliente, true);
        $saldo = fn(): int => (int) $this->conexion->query("SELECT puntos FROM tbl_clientes WHERE ID = $cliente")->fetchColumn();
        // $20.000: usa 250 puntos ($5.000, el 25 %), paga $15.000 y gana 10
        $this->assertSame(1000 - 250 + 10, $saldo());

        $resultado = $this->pedidos->actualizar($pedido, 'Cancelado', 'Si', 'empleado');

        $this->assertSame(['Cancelado', false], [$resultado['estado'], $resultado['pagado']], 'cancelado queda sin pagar');
        $this->assertSame(1000, $saldo(), 'recupera los usados y pierde los ganados');
        $ultimo = $this->conexion->query("SELECT tipo, puntos, saldo_resultante FROM movimientos_puntos WHERE cliente_id = $cliente ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_NUM);
        $this->assertEquals(['ajuste', 240, 1000], $ultimo);
        $this->assertSame(10.0, $this->stock($insumo), 'cancelado en preparación no descuenta stock');

        $this->rechaza(fn() => $this->pedidos->actualizar($pedido, 'Listo', null, 'admin'), 409);
        $this->rechaza(fn() => $this->pedidos->actualizar($pedido, null, 'Si', 'admin'), 409);
    }

    public function testElPagoYLosDatosInvalidos(): void
    {
        ['pedido' => $pedido] = $this->pedido();
        $this->assertTrue($this->pedidos->actualizar($pedido, null, 'Si', 'empleado')['pagado']);
        $this->rechaza(fn() => $this->pedidos->actualizar($pedido, null, 'No', 'delivery'), 403);
        $this->rechaza(fn() => $this->pedidos->actualizar($pedido, null, 'Tal vez', 'empleado'), 422);
        $this->rechaza(fn() => $this->pedidos->actualizar($pedido, null, null, 'empleado'), 422);
        $this->rechaza(fn() => $this->pedidos->actualizar(999999, 'Listo', null, 'admin'), 404);

        $cocina = array_column($this->pedidos->cocina(), null, 'id');
        $this->assertSame([['nombre' => 'Prueba producto', 'cantidad' => 2]], $cocina[$pedido]['productos']);
    }
}
