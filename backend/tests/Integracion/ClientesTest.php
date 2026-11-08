<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\ClientesController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ClientesRepositorio;

final class ClientesTest extends BaseDePruebas
{
    private ClientesController $clientes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientes = new ClientesController(new ClientesRepositorio($this->conexion));
    }

    /** @return array{cliente: array<string, mixed>, password_provisoria: string} */
    private function nuevoCliente(array $cambios = []): array
    {
        $creado = $this->clientes->crear($cambios + [
            'nombre' => 'Prueba cliente', 'email' => 'prueba.cliente@ejemplo.test',
            'codigo_pais' => '54', 'telefono' => '3519990001', 'puntos' => '150',
        ]);
        $this->anotar('tbl_clientes', $creado['cliente']['id']);

        return $creado;
    }

    /** @return array<int, array<string, mixed>> */
    private function movimientos(int $id): array
    {
        return $this->clientes->detalle($id)['movimientos'];
    }

    public function testCreaUnClienteConContrasenaProvisoriaYPuntosIniciales(): void
    {
        $creado = $this->nuevoCliente();
        $cliente = $creado['cliente'];

        $this->assertSame('+543519990001', $cliente['telefono']);
        $this->assertSame(['54', '3519990001'], [$cliente['codigo_pais'], $cliente['numero']]);
        $this->assertSame(150, $cliente['puntos']);

        $hash = (string) $this->conexion->query("SELECT password FROM tbl_clientes WHERE ID = {$cliente['id']}")->fetchColumn();
        $this->assertTrue(password_verify($creado['password_provisoria'], $hash), 'se guarda la contraseña que se mostró');
        $this->assertFalse(password_verify('12345', $hash), 'ya no se usa la contraseña genérica');

        $movimientos = $this->movimientos($cliente['id']);
        $this->assertCount(1, $movimientos);
        $this->assertSame([150, 150, 'ajuste'], [$movimientos[0]['puntos'], $movimientos[0]['saldo'], $movimientos[0]['tipo']]);
    }

    public function testValidaCadaCampo(): void
    {
        try {
            $this->clientes->crear(['nombre' => '', 'email' => 'no-es-un-mail', 'codigo_pais' => '999', 'telefono' => '123', 'puntos' => '-5']);
            $this->fail('Debería rechazar los datos');
        } catch (ApiException $error) {
            $this->assertSame(['nombre', 'email', 'codigo_pais', 'puntos'], array_keys($error->errores()));
        }

        try {
            $this->clientes->crear(['nombre' => 'X', 'email' => 'x@ejemplo.test', 'codigo_pais' => '54', 'telefono' => '12', 'puntos' => '1.5']);
            $this->fail('Debería rechazar el teléfono y los puntos con decimales');
        } catch (ApiException $error) {
            $this->assertSame(['telefono', 'puntos'], array_keys($error->errores()));
        }
    }

    public function testNoRepiteEmailNiTelefonoDeOtroCliente(): void
    {
        $primero = $this->nuevoCliente()['cliente'];
        try {
            $this->nuevoCliente(['email' => 'PRUEBA.cliente@ejemplo.test', 'telefono' => '351 999-0001']);
            $this->fail('Debería rechazar los datos repetidos');
        } catch (ApiException $error) {
            $this->assertSame(['email', 'telefono'], array_keys($error->errores()));
        }

        // Guardarlo con sus propios datos no es repetir
        $editado = $this->clientes->actualizar($primero['id'], ['nombre' => 'Otro nombre', 'email' => $primero['email'], 'codigo_pais' => '54', 'telefono' => '3519990001', 'puntos' => 150]);
        $this->assertSame('Otro nombre', $editado['nombre']);
    }

    public function testCambiarLosPuntosAnotaSoloLaDiferencia(): void
    {
        $cliente = $this->nuevoCliente()['cliente'];
        $datos = ['nombre' => 'Prueba cliente', 'email' => 'prueba.cliente@ejemplo.test', 'codigo_pais' => '54', 'telefono' => '3519990001'];

        $this->clientes->actualizar($cliente['id'], $datos + ['puntos' => 100]);
        $this->clientes->actualizar($cliente['id'], $datos + ['puntos' => 100]);

        $movimientos = $this->movimientos($cliente['id']);
        $this->assertCount(2, $movimientos, 'guardar sin cambiar los puntos no anota nada');
        $this->assertSame([-50, 100], [$movimientos[0]['puntos'], $movimientos[0]['saldo']]);
    }

    public function testAlBorrarloSusPedidosQuedanEnElHistorial(): void
    {
        $cliente = $this->nuevoCliente()['cliente'];
        $pedido = $this->insertar('tbl_pedidos', [
            'nombre' => 'Prueba', 'telefono' => '+543519990001', 'total' => 100, 'metodo_pago' => 'Efectivo',
            'tipo_entrega' => 'Retiro', 'estado' => 'Entregado', 'esta_pago' => 'Si', 'cliente_id' => $cliente['id'],
        ]);
        $this->assertSame($pedido, $this->clientes->detalle($cliente['id'])['pedidos'][0]['id']);

        $this->clientes->eliminar($cliente['id']);

        $this->assertNull($this->conexion->query("SELECT cliente_id FROM tbl_pedidos WHERE ID = $pedido")->fetchColumn());
        $this->assertSame(0, (int) $this->conexion->query("SELECT COUNT(*) FROM movimientos_puntos WHERE cliente_id = {$cliente['id']}")->fetchColumn());
        $this->expectException(ApiException::class);
        $this->clientes->obtener($cliente['id']);
    }
}
