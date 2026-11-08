<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\UsuariosController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\UsuariosRepositorio;

final class UsuariosTest extends BaseDePruebas
{
    private UsuariosController $usuarios;
    /** Administrador que "está usando el panel" en cada prueba */
    private int $yo;

    protected function setUp(): void
    {
        parent::setUp();
        require_once \piccolo_path('src/Services/Security/password_utils.php');
        $this->usuarios = new UsuariosController(new UsuariosRepositorio($this->conexion));
        $this->yo = $this->insertar('tbl_usuarios', [
            'usuario' => 'prueba_admin', 'correo' => 'prueba.admin@ejemplo.test', 'rol' => 'admin', 'password' => generarHashPassword('MiClave1!'),
        ]);
    }

    private function nuevo(array $cambios = []): array
    {
        $usuario = $this->usuarios->crear($cambios + [
            'usuario' => 'prueba_empleado', 'correo' => 'prueba.empleado@ejemplo.test', 'rol' => 'empleado',
            'password' => 'Empleado1!', 'confirmar' => 'Empleado1!',
        ], $this->yo);
        $this->anotar('tbl_usuarios', $usuario['id']);

        return $usuario;
    }

    public function testCreaUnEmpleadoConSuContrasena(): void
    {
        $usuario = $this->nuevo();

        $this->assertSame(['prueba_empleado', 'empleado'], [$usuario['usuario'], $usuario['rol']]);
        $hash = (string) $this->conexion->query("SELECT password FROM tbl_usuarios WHERE ID = {$usuario['id']}")->fetchColumn();
        $this->assertTrue(password_verify('Empleado1!', $hash));
    }

    public function testValidaLosDatosYLaContrasena(): void
    {
        try {
            $this->nuevo(['usuario' => 'ab', 'correo' => 'mal', 'rol' => 'jefe']);
            $this->fail('Debería rechazar los datos');
        } catch (ApiException $error) {
            $this->assertSame(['usuario', 'correo', 'rol'], array_keys($error->errores()));
        }
        foreach ([['password' => 'debil', 'confirmar' => 'debil'], ['confirmar' => 'Otra1234!']] as $cambios) {
            try {
                $this->nuevo($cambios);
                $this->fail('Debería rechazar la contraseña');
            } catch (ApiException $error) {
                $this->assertCount(1, $error->errores());
            }
        }
        try {
            $this->nuevo(['usuario' => 'PRUEBA_ADMIN', 'correo' => 'prueba.admin@ejemplo.test']);
            $this->fail('Debería rechazar datos repetidos');
        } catch (ApiException $error) {
            $this->assertSame(['usuario', 'correo'], array_keys($error->errores()));
        }
    }

    public function testParaTocarAdministradoresHayQueConfirmarConLaPropiaContrasena(): void
    {
        $empleado = $this->nuevo();
        $datos = ['usuario' => 'prueba_empleado', 'correo' => 'prueba.empleado@ejemplo.test', 'rol' => 'admin'];

        foreach (['', 'Empleado1!'] as $password) { // vacía, o la del usuario editado en vez de la propia
            try {
                $this->usuarios->actualizar($empleado['id'], $datos + ['password_actual' => $password], $this->yo);
                $this->fail('Debería pedir la contraseña propia');
            } catch (ApiException $error) {
                $this->assertSame(['password_actual'], array_keys($error->errores()));
            }
        }
        $this->assertSame('empleado', $this->usuarios->obtener($empleado['id'])['rol'], 'sin confirmar no se guarda nada');

        $this->assertSame('admin', $this->usuarios->actualizar($empleado['id'], $datos + ['password_actual' => 'MiClave1!'], $this->yo)['rol']);

        // Un empleado común se edita sin confirmar
        $otro = $this->nuevo(['usuario' => 'prueba_otro', 'correo' => 'prueba.otro@ejemplo.test']);
        $editado = $this->usuarios->actualizar($otro['id'], ['usuario' => 'prueba_otro2', 'correo' => 'prueba.otro@ejemplo.test', 'rol' => 'delivery'], $this->yo);
        $this->assertSame(['prueba_otro2', 'delivery'], [$editado['usuario'], $editado['rol']]);
    }

    public function testNadieSeBorraASiMismo(): void
    {
        try {
            $this->usuarios->eliminar($this->yo, $this->yo);
            $this->fail('No debería poder borrarse');
        } catch (ApiException $error) {
            $this->assertSame(409, $error->status());
        }

        $empleado = $this->nuevo();
        $this->usuarios->eliminar($empleado['id'], $this->yo);
        $this->expectException(ApiException::class);
        $this->usuarios->obtener($empleado['id']);
    }

    public function testLaListaMarcaQuienEsVos(): void
    {
        $lista = array_column($this->usuarios->listar($this->yo), null, 'id');

        $this->assertTrue($lista[$this->yo]['sos_vos']);
        $this->assertFalse($lista[$this->yo]['se_puede_borrar']);
        $this->assertArrayNotHasKey('password', $lista[$this->yo]);
    }
}
