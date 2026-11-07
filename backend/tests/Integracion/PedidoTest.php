<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use PedidoException;

final class PedidoTest extends BaseDePruebas
{
    private function datosPedido(int $productoId, int $cantidad, array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Prueba cliente', 'telefono' => '3511234567', 'codigo_pais' => '54',
            'metodo_pago' => 'Efectivo', 'tipo_entrega' => 'Retiro',
            'items' => [['id' => $productoId, 'cantidad' => $cantidad]],
        ], $extra);
    }

    public function testElTotalSeCalculaConLosPreciosDeLaBase(): void
    {
        $producto = $this->crearProductoConStock(2500, 10)['producto'];

        // Aunque el navegador mande otro precio, se cobra el de la base
        $datos = $this->datosPedido($producto, 3);
        $datos['items'][0]['precio'] = 1;
        $resultado = crearPedido($this->conexion, $datos);
        $this->anotar('tbl_pedidos', $resultado['pedido_id']);

        $this->assertSame(7500.0, $resultado['total']);
        $guardado = $this->conexion->query("SELECT total, estado FROM tbl_pedidos WHERE ID = {$resultado['pedido_id']}")->fetch();
        $this->assertEquals(7500, $guardado['total']);
        $this->assertSame('En preparación', $guardado['estado']);

        $detalle = $this->conexion->query("SELECT cantidad, precio FROM tbl_pedidos_detalle WHERE pedido_id = {$resultado['pedido_id']}")->fetchAll();
        $this->assertCount(1, $detalle);
        $this->assertEquals(3, $detalle[0]['cantidad']);
    }

    public function testSinStockNoSeRegistraElPedido(): void
    {
        $producto = $this->crearProductoConStock(1000, 2)['producto'];
        $pedidosAntes = (int) $this->conexion->query('SELECT COUNT(*) FROM tbl_pedidos')->fetchColumn();

        try {
            crearPedido($this->conexion, $this->datosPedido($producto, 3));
            $this->fail('Debería haber rechazado el pedido por falta de stock');
        } catch (PedidoException $e) {
            $this->assertStringContainsString('inconveniente', $e->getMessage());
        }

        $this->assertSame($pedidosAntes, (int) $this->conexion->query('SELECT COUNT(*) FROM tbl_pedidos')->fetchColumn());
    }

    public function testUnProductoOcultoNoSePuedePedir(): void
    {
        $producto = $this->crearProductoConStock(1000, 10)['producto'];
        $this->conexion->exec("UPDATE tbl_menu SET visible_en_menu = 0 WHERE ID = $producto");

        $this->expectException(PedidoException::class);
        $this->expectExceptionMessage('ya no está disponible');
        crearPedido($this->conexion, $this->datosPedido($producto, 1));
    }

    public function testElClienteCanjeaYSumaPuntos(): void
    {
        $producto = $this->crearProductoConStock(10000, 10)['producto'];
        $cliente = $this->insertar('tbl_clientes', [
            'nombre' => 'Prueba cliente', 'telefono' => '+540000000001', 'password' => 'x', 'puntos' => 1000,
        ]);
        $this->conexion->exec('UPDATE tbl_configuracion_puntos SET minimo_puntos = 50, valor_punto = 20, maximo_porcentaje = 0.25 WHERE id = 1');

        $resultado = crearPedido($this->conexion, $this->datosPedido($producto, 1, ['usar_puntos' => true]), $cliente);
        $this->anotar('tbl_pedidos', $resultado['pedido_id']);

        // 25 % de $10.000 = $2.500 de descuento (125 puntos); paga $7.500 y gana 5 puntos
        $this->assertSame(125, $resultado['puntos_usados']);
        $this->assertSame(2500.0, $resultado['descuento']);
        $this->assertSame(7500.0, $resultado['total']);
        $this->assertSame(5, $resultado['puntos_ganados']);

        $puntos = (int) $this->conexion->query("SELECT puntos FROM tbl_clientes WHERE ID = $cliente")->fetchColumn();
        $this->assertSame(1000 - 125 + 5, $puntos);
    }
}
