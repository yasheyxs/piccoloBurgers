<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\ContrasenaPanelController;
use Piccolo\Http\Api\ApiException;

final class ContrasenaPanelTest extends BaseDePruebas
{
    private ContrasenaPanelController $contrasenas;
    private int $usuario;
    private string $correo;
    /** @var array<int, array{0: string, 1: string, 2: string}> correos "enviados" (correo, usuario, enlace) */
    private array $enviados = [];

    protected function setUp(): void
    {
        parent::setUp();
        require_once \piccolo_path('src/Services/Security/password_utils.php');
        $this->contrasenas = new ContrasenaPanelController($this->conexion);
        // Un correo distinto en cada prueba, así el límite de pedidos no se arrastra entre pruebas
        $this->correo = 'prueba.' . bin2hex(random_bytes(4)) . '@ejemplo.test';
        $this->usuario = $this->insertar('tbl_usuarios', [
            'usuario' => 'prueba_' . bin2hex(random_bytes(3)), 'correo' => $this->correo, 'rol' => 'empleado', 'password' => generarHashPassword('Actual123!'),
        ]);
    }

    private function pedirEnlace(string $correo, bool $seEnvia = true): void
    {
        $this->contrasenas->solicitar($correo, function (string $correo, string $usuario, string $enlace) use ($seEnvia): bool {
            $this->enviados[] = [$correo, $usuario, $enlace];
            return $seEnvia;
        }, 'http://panel/password/reset_password.php');
    }

    private function hash(): string
    {
        return (string) $this->conexion->query("SELECT password FROM tbl_usuarios WHERE ID = {$this->usuario}")->fetchColumn();
    }

    public function testCambiarLaPropiaContrasena(): void
    {
        try {
            $this->contrasenas->cambiar($this->usuario, ['actual' => 'otra', 'nueva' => 'debil', 'confirmar' => 'debil']);
            $this->fail('Debería rechazar');
        } catch (ApiException $error) {
            $this->assertSame(['actual', 'nueva'], array_keys($error->errores()));
        }
        try {
            $this->contrasenas->cambiar($this->usuario, ['actual' => 'Actual123!', 'nueva' => 'Actual123!', 'confirmar' => 'Actual123!']);
            $this->fail('La nueva tiene que ser distinta');
        } catch (ApiException $error) {
            $this->assertSame(['nueva'], array_keys($error->errores()));
        }

        $this->contrasenas->cambiar($this->usuario, ['actual' => 'Actual123!', 'nueva' => 'Nueva1234!', 'confirmar' => 'Nueva1234!']);
        $this->assertTrue(password_verify('Nueva1234!', $this->hash()));
    }

    public function testRecuperarPorCorreo(): void
    {
        $this->pedirEnlace($this->correo);

        $this->assertCount(1, $this->enviados);
        parse_str((string) parse_url($this->enviados[0][2], PHP_URL_QUERY), $query);
        $token = $query['token'];
        $guardado = (string) $this->conexion->query("SELECT reset_token FROM tbl_usuarios WHERE ID = {$this->usuario}")->fetchColumn();
        $this->assertSame(hash('sha256', $token), $guardado, 'en la base queda solo la huella del enlace');
        $this->assertNotNull($this->contrasenas->usuarioDelEnlace($token));

        $this->contrasenas->restablecer($token, ['nueva' => 'Recuperada1!', 'confirmar' => 'Recuperada1!']);
        $this->assertTrue(password_verify('Recuperada1!', $this->hash()));
        $this->assertNull($this->contrasenas->usuarioDelEnlace($token), 'el enlace sirve una sola vez');

        $this->expectException(ApiException::class);
        $this->contrasenas->restablecer($token, ['nueva' => 'OtraVez123!', 'confirmar' => 'OtraVez123!']);
    }

    public function testUnCorreoQueNoExisteNoSeDistingue(): void
    {
        $this->pedirEnlace('nadie.' . bin2hex(random_bytes(4)) . '@ejemplo.test');

        $this->assertSame([], $this->enviados, 'no se manda nada, pero tampoco hay error');
        $this->assertNull($this->contrasenas->usuarioDelEnlace(str_repeat('a', 64)));
        $this->assertNull($this->contrasenas->usuarioDelEnlace('<script>'));
    }

    public function testDemasiadosPedidosSeguidosSeFrenan(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->pedirEnlace($this->correo, false); // aunque el correo falle, el enlace no se muestra: solo cuenta el intento
        }
        try {
            $this->pedirEnlace($this->correo);
            $this->fail('El sexto pedido seguido debería esperar');
        } catch (ApiException $error) {
            $this->assertSame(429, $error->status());
        }
    }
}
