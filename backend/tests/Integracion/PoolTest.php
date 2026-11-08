<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\PoolController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PoolRepositorio;

final class PoolTest extends BaseDePruebas
{
    private PoolController $pool;
    /** @var array<string, mixed> */
    private array $configuracionOriginal;
    /** @var int[] jornadas que estaban abiertas antes de la prueba (se cierran para probar sin interferencias y se reabren al final) */
    private array $abiertas = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pool = new PoolController(new PoolRepositorio($this->conexion), $this->conexion);
        $this->configuracionOriginal = $this->pool->configuracion();
        $this->pool->guardarConfiguracion(['max_fichas_por_registro' => 3, 'valor_ficha' => 1000]);

        $this->abiertas = array_map('intval', $this->conexion->query("SELECT id FROM pool_jornadas WHERE estado = 'abierta'")->fetchAll(\PDO::FETCH_COLUMN));
        $this->conexion->exec("UPDATE pool_jornadas SET estado = 'cerrada' WHERE estado = 'abierta'");
    }

    protected function tearDown(): void
    {
        if (isset($this->pool)) {
            foreach ($this->abiertas as $id) {
                $this->conexion->exec("UPDATE pool_jornadas SET estado = 'abierta' WHERE id = $id");
            }
            $this->pool->guardarConfiguracion($this->configuracionOriginal);
        }
        parent::tearDown();
    }

    private function abrir(): int
    {
        $this->pool->abrirJornada();
        $id = (int) $this->pool->estado()['jornada']['id'];
        $this->anotar('pool_jornadas', $id);

        return $id;
    }

    /** @return array<int, array<string, mixed>> */
    private function fila(string $mesa): array
    {
        return $this->pool->estado()['pools'][$mesa]['turnos'];
    }

    public function testSinJornadaNoSeOpera(): void
    {
        try {
            $this->pool->agregar(['pool' => 'azul', 'nombre' => 'Ana', 'fichas' => 2]);
            $this->fail('Debería pedir abrir la jornada');
        } catch (ApiException $error) {
            $this->assertSame(409, $error->status());
        }
    }

    public function testNoSeAbrenDosJornadas(): void
    {
        $this->abrir();
        $this->expectException(ApiException::class);
        $this->pool->abrirJornada();
    }

    public function testLasFichasSeRepartenEnTurnosSegunElMaximo(): void
    {
        $this->abrir();
        $this->pool->agregar(['pool' => 'azul', 'nombre' => 'Ana', 'fichas' => 5]);

        $fila = $this->fila('azul');
        $this->assertSame([['Ana', 3], ['Ana', 2]], array_map(fn($t) => [$t['nombre'], $t['fichas_total']], $fila));
    }

    public function testValidaLosTurnos(): void
    {
        $this->abrir();
        foreach ([
            [['pool' => 'verde', 'nombre' => 'Ana', 'fichas' => 1], 'pool'],
            [['pool' => 'azul', 'nombre' => '', 'fichas' => 1], 'nombre'],
            [['pool' => 'azul', 'nombre' => 'Ana', 'fichas' => 0], 'fichas'],
            [['pool' => 'azul', 'nombre' => 'Ana', 'fichas' => 100], 'fichas'],
        ] as [$datos, $campo]) {
            try {
                $this->pool->agregar($datos);
                $this->fail('Debería rechazar ' . json_encode($datos));
            } catch (ApiException $error) {
                $this->assertSame([$campo], array_keys($error->errores()));
            }
        }
    }

    public function testUsarYDevolverFichas(): void
    {
        $this->abrir();
        $this->pool->agregar(['pool' => 'rojo', 'nombre' => 'Beto', 'fichas' => 1]);
        $turno = $this->fila('rojo')[0]['id'];

        $this->pool->consumir($turno);
        $this->assertSame([], $this->fila('rojo'), 'con todas las fichas usadas sale de la fila');
        try {
            $this->pool->consumir($turno);
            $this->fail('No le quedan fichas');
        } catch (ApiException $error) {
            $this->assertSame(409, $error->status());
        }

        $this->pool->revertir($turno);
        $this->assertSame(0, $this->fila('rojo')[0]['fichas_consumidas'], 'al devolverla vuelve a la fila');
        $this->expectException(ApiException::class);
        $this->pool->revertir($turno);
    }

    public function testMoverYPasarDeMesa(): void
    {
        $this->abrir();
        foreach (['Ana', 'Beto', 'Caro'] as $nombre) {
            $this->pool->agregar(['pool' => 'azul', 'nombre' => $nombre, 'fichas' => 1]);
        }
        $this->pool->agregar(['pool' => 'rojo', 'nombre' => 'Dani', 'fichas' => 1]);
        $ids = array_column($this->fila('azul'), 'id', 'nombre');

        $this->pool->mover($ids['Caro'], ['direccion' => 'arriba']);
        $this->assertSame(['Ana', 'Caro', 'Beto'], array_column($this->fila('azul'), 'nombre'));

        $this->pool->transferir($ids['Ana'], ['pool' => 'rojo']);
        $this->assertSame(['Caro', 'Beto'], array_column($this->fila('azul'), 'nombre'));
        $this->assertSame(['Dani', 'Ana'], array_column($this->fila('rojo'), 'nombre'), 'pasa al final de la otra fila');

        $this->pool->reordenar(['pool' => 'azul', 'ids' => [$ids['Beto'], $ids['Caro']]]);
        $this->assertSame(['Beto', 'Caro'], array_column($this->fila('azul'), 'nombre'));
    }

    public function testLosMontosSoloSeVenConLaContrasena(): void
    {
        require_once \piccolo_path('src/Services/Security/password_utils.php');
        $usuario = $this->insertar('tbl_usuarios', [
            'usuario' => 'prueba_pool', 'correo' => 'prueba.pool@ejemplo.test', 'rol' => 'empleado', 'password' => generarHashPassword('Pool1234!'),
        ]);
        $this->abrir();
        $this->pool->agregar(['pool' => 'azul', 'nombre' => 'Ana', 'fichas' => 2]);

        $this->assertNull($this->pool->estado()['pools']['azul']['stats']['montoVendido']);
        try {
            $this->pool->mostrarSensibles($usuario, 'otra');
            $this->fail('Con una contraseña incorrecta no se muestran');
        } catch (ApiException $error) {
            $this->assertSame(['password'], array_keys($error->errores()));
        }
        $stats = $this->pool->mostrarSensibles($usuario, 'Pool1234!')['pools']['azul']['stats'];
        $this->assertSame([2, 2000.0], [$stats['vendidas'], $stats['montoVendido']]);
    }

    public function testAlCerrarSeGuardaLaCorreccionDeTotales(): void
    {
        $jornada = $this->abrir();
        $this->pool->agregar(['pool' => 'azul', 'nombre' => 'Ana', 'fichas' => 2]);
        $this->assertSame(2, $this->pool->totalesCierre()['fichasVendidas']);

        $this->pool->cerrarJornada(['fichasVendidas' => 3]);

        $this->assertNull($this->pool->estado()['jornada']);
        $guardada = $this->conexion->query("SELECT estado, ajuste_fichas_vendidas FROM pool_jornadas WHERE id = $jornada")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame(['cerrada', 1], [$guardada['estado'], (int) $guardada['ajuste_fichas_vendidas']], 'se vendió una ficha más de las anotadas');

        $this->expectException(ApiException::class);
        $this->pool->cerrarJornada(['fichasVendidas' => -1]);
    }

    public function testValidaLosParametros(): void
    {
        try {
            $this->pool->guardarConfiguracion(['max_fichas_por_registro' => 0, 'valor_ficha' => '-5']);
            $this->fail('Debería rechazar los valores');
        } catch (ApiException $error) {
            $this->assertSame(['max_fichas_por_registro', 'valor_ficha'], array_keys($error->errores()));
        }
        $this->assertSame(1500.5, $this->pool->guardarConfiguracion(['max_fichas_por_registro' => 4, 'valor_ficha' => '1500,5'])['valor_ficha']);
    }
}
