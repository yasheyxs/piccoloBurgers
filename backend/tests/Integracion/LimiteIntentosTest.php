<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

final class LimiteIntentosTest extends BaseDePruebas
{
    private string $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $this->usuario = 'prueba-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        if (isset($this->conexion)) {
            piccoloLoginRegistrarExito($this->conexion, 'panel', $this->usuario);
        }
        parent::tearDown();
    }

    public function testCincoFallosBloqueanAlUsuario(): void
    {
        for ($i = 1; $i <= PICCOLO_LOGIN_MAX_INTENTOS - 1; $i++) {
            piccoloLoginRegistrarFallo($this->conexion, 'panel', $this->usuario);
        }
        $this->assertSame(0, piccoloLoginMinutosBloqueado($this->conexion, 'panel', $this->usuario), 'con 4 fallos todavía puede probar');

        piccoloLoginRegistrarFallo($this->conexion, 'panel', $this->usuario);
        $minutos = piccoloLoginMinutosBloqueado($this->conexion, 'panel', $this->usuario);

        $this->assertGreaterThan(0, $minutos);
        $this->assertLessThanOrEqual(PICCOLO_LOGIN_MINUTOS_BLOQUEO, $minutos);
    }

    public function testElBloqueoEsSoloParaEseUsuarioEIp(): void
    {
        for ($i = 0; $i < PICCOLO_LOGIN_MAX_INTENTOS; $i++) {
            piccoloLoginRegistrarFallo($this->conexion, 'panel', $this->usuario);
        }

        $this->assertSame(0, piccoloLoginMinutosBloqueado($this->conexion, 'cliente', $this->usuario));

        $_SERVER['REMOTE_ADDR'] = '203.0.113.8';
        $this->assertSame(0, piccoloLoginMinutosBloqueado($this->conexion, 'panel', $this->usuario));
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
    }

    public function testUnIngresoCorrectoBorraElContador(): void
    {
        for ($i = 0; $i < PICCOLO_LOGIN_MAX_INTENTOS - 1; $i++) {
            piccoloLoginRegistrarFallo($this->conexion, 'panel', $this->usuario);
        }
        piccoloLoginRegistrarExito($this->conexion, 'panel', $this->usuario);
        piccoloLoginRegistrarFallo($this->conexion, 'panel', $this->usuario);

        $this->assertSame(0, piccoloLoginMinutosBloqueado($this->conexion, 'panel', $this->usuario));
    }
}
