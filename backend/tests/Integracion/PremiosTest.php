<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\PremiosController;
use Piccolo\Http\Admin\PuntosController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PremiosRepositorio;

final class PremiosTest extends BaseDePruebas
{
    private PremiosController $premios;
    private PuntosController $puntos;
    /** @var array<string, mixed> configuración de puntos antes de cada prueba, para dejarla como estaba */
    private array $configuracionOriginal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->premios = new PremiosController(new PremiosRepositorio($this->conexion));
        $this->puntos = new PuntosController($this->conexion);
        $this->configuracionOriginal = $this->puntos->obtener();
    }

    protected function tearDown(): void
    {
        if (isset($this->puntos)) {
            $this->puntos->actualizar($this->configuracionOriginal);
        }
        parent::tearDown();
    }

    public function testCreaEditaYBorraUnPremio(): void
    {
        $premio = $this->premios->crear(['nombre' => ' Prueba premio ', 'descripcion' => '', 'costo_puntos' => '120']);
        $this->anotar('premios', $premio['id']);
        $this->assertSame(['Prueba premio', null, 120], [$premio['nombre'], $premio['descripcion'], $premio['costo_puntos']]);

        $editado = $this->premios->actualizar($premio['id'], ['nombre' => 'Prueba premio', 'descripcion' => 'Con papas', 'costo_puntos' => 90]);
        $this->assertSame(['Con papas', 90], [$editado['descripcion'], $editado['costo_puntos']]);

        $this->premios->eliminar($premio['id']);
        $this->expectException(ApiException::class);
        $this->premios->obtener($premio['id']);
    }

    public function testValidaElPremio(): void
    {
        foreach (['0', '-5', '1.5', 'diez', ''] as $costo) {
            try {
                $this->premios->crear(['nombre' => '', 'costo_puntos' => $costo, 'descripcion' => str_repeat('x', 1001)]);
                $this->fail("Debería rechazar el costo '$costo'");
            } catch (ApiException $error) {
                $this->assertSame(['nombre', 'descripcion', 'costo_puntos'], array_keys($error->errores()));
            }
        }
    }

    public function testGuardaLaConfiguracionDePuntosConPorcentajeYComa(): void
    {
        $guardada = $this->puntos->actualizar(['minimo_puntos' => '40', 'valor_punto' => '12,5', 'maximo_porcentaje' => '30']);

        $this->assertSame([40, 12.5, 30.0], [$guardada['minimo_puntos'], $guardada['valor_punto'], $guardada['maximo_porcentaje']]);
        $this->assertEqualsWithDelta(0.3, (float) $this->conexion->query('SELECT maximo_porcentaje FROM tbl_configuracion_puntos WHERE id = 1')->fetchColumn(), 0.0001, 'en la base queda como fracción');

        // Compra de $10.000: hasta el 30 % ($3.000) = 240 puntos de $12,50; se juntan 6 puntos por compra
        $this->assertSame(['maximo_puntos' => 240, 'maximo_descuento' => 3000.0, 'compras_para_el_minimo' => 7], array_diff_key($guardada['ejemplo'], ['compra' => 0]));
    }

    public function testRechazaUnaConfiguracionFueraDeRango(): void
    {
        try {
            $this->puntos->actualizar(['minimo_puntos' => '1.5', 'valor_punto' => '0', 'maximo_porcentaje' => '0']);
            $this->fail('Debería rechazar los valores');
        } catch (ApiException $error) {
            $this->assertSame(['minimo_puntos', 'valor_punto', 'maximo_porcentaje'], array_keys($error->errores()));
        }
        $this->assertSame($this->configuracionOriginal['valor_punto'], $this->puntos->obtener()['valor_punto'], 'no se guardó nada');
    }
}
