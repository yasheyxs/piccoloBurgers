<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ClientesRepositorio;

/**
 * Reglas de la sección Clientes (alta, edición, detalle y baja). La usan las pantallas del panel
 * y la API (/api/v1/admin/clientes). El canje de puntos tiene su propia pantalla.
 */
final class ClientesController
{
    private const NO_EXISTE = 'El cliente no existe o ya fue eliminado.';

    public function __construct(private ClientesRepositorio $repositorio)
    {
        require_once \piccolo_path('src/Http/Admin/telefonos.php');
        require_once \piccolo_path('src/Services/Security/validar_telefono.php');
        require_once \piccolo_path('src/Services/Security/password_utils.php');
    }

    /** @return array<string, array{bandera: string, longitudes: int[]}> códigos de país que acepta el formulario */
    public static function codigosPais(): array
    {
        require_once \piccolo_path('src/Http/Admin/telefonos.php');
        $codigos = [];
        foreach (\obtenerCatalogoTelefonos() as $codigo => $datos) {
            $codigos[(string) $codigo] = ['bandera' => $datos['bandera'], 'longitudes' => $datos['longitudes']];
        }

        return $codigos;
    }

    /** @return array<int, array<string, mixed>> */
    public function listar(): array
    {
        return array_map([self::class, 'formato'], $this->repositorio->listar());
    }

    /** @return array<string, mixed> */
    public function obtener(int $id): array
    {
        $cliente = $this->repositorio->buscar($id);
        if ($cliente === null) {
            throw ApiException::noEncontrado(self::NO_EXISTE);
        }

        return self::formato($cliente);
    }

    /**
     * Cliente con su historial de pedidos y de puntos.
     *
     * @return array<string, mixed>
     */
    public function detalle(int $id): array
    {
        $cliente = $this->obtener($id);
        $pedidos = array_map(static fn(array $p): array => [
            'id' => (int) $p['ID'],
            'fecha' => (string) $p['fecha'],
            'total' => (float) $p['total'],
            'estado' => (string) $p['estado'],
            'metodo_pago' => (string) $p['metodo_pago'],
            'tipo_entrega' => (string) $p['tipo_entrega'],
        ], $this->repositorio->pedidos($id));

        $mayor = null;
        foreach ($pedidos as $pedido) {
            if ($mayor === null || $pedido['total'] > $mayor['total']) {
                $mayor = $pedido;
            }
        }

        $movimientos = array_map(static fn(array $m): array => [
            'fecha' => $m['created_at'] !== null ? (string) $m['created_at'] : null,
            'tipo' => (string) $m['tipo'],
            'descripcion' => (string) ($m['descripcion'] ?? ''),
            'puntos' => (int) $m['puntos'],
            'saldo' => (int) $m['saldo_resultante'],
        ], $this->repositorio->movimientos($id));

        return $cliente + ['pedido_mayor' => $mayor, 'pedidos' => $pedidos, 'movimientos' => $movimientos];
    }

    /**
     * Crea el cliente con una contraseña provisoria al azar, que se muestra una sola vez para dársela al cliente.
     * (Antes todos quedaban con "12345": cualquiera que supiera el teléfono podía entrar a la cuenta.)
     *
     * @return array{cliente: array<string, mixed>, password_provisoria: string}
     */
    public function crear(array $datos): array
    {
        $validos = $this->validar($datos);
        $password = self::passwordProvisoria();
        $id = $this->repositorio->crear($validos, \generarHashPassword($password));

        return ['cliente' => $this->obtener($id), 'password_provisoria' => $password];
    }

    /** @return array<string, mixed> */
    public function actualizar(int $id, array $datos): array
    {
        $this->obtener($id);
        $this->repositorio->actualizar($id, $this->validar($datos, $id));

        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        $this->obtener($id);
        $this->repositorio->eliminar($id);
    }

    /** Contraseña difícil de adivinar que igual cumple los requisitos del sitio (ej.: "Pb-k7mqx2ra5"). */
    public static function passwordProvisoria(): string
    {
        $letras = 'abcdefghjkmnpqrstuvwxyz23456789';
        $texto = '';
        for ($i = 0; $i < 8; $i++) {
            $texto .= $letras[random_int(0, strlen($letras) - 1)];
        }

        return 'Pb-' . $texto . random_int(0, 9);
    }

    /**
     * @return array{nombre: string, telefono: string, email: string, puntos: int}
     * @throws ApiException 422 con los errores por campo
     */
    private function validar(array $datos, int $id = 0): array
    {
        $nombre = Datos::texto($datos['nombre'] ?? '');
        $email = Datos::texto($datos['email'] ?? '');
        $codigo = \normalizarCodigoTelefono(Datos::texto($datos['codigo_pais'] ?? '54'));
        $numero = Datos::texto($datos['telefono'] ?? '');
        $puntosTexto = Datos::texto($datos['puntos'] ?? '0');
        $errores = [];

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 120) {
            $errores['nombre'] = 'El nombre puede tener hasta 120 caracteres.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 160) {
            $errores['email'] = 'Ingresá un correo electrónico válido.';
        } elseif ($this->repositorio->enUso('email', $email, $id)) {
            $errores['email'] = 'Ya hay un cliente con ese correo electrónico.';
        }

        $telefono = '';
        if (!array_key_exists($codigo, self::codigosPais())) {
            $errores['codigo_pais'] = 'Elegí un código de país de la lista.';
        } else {
            $telefono = \validarTelefono($codigo, $numero) ?: '';
            if ($telefono === '') {
                $errores['telefono'] = 'Ingresá un número de teléfono válido, sin el código de país.';
            } elseif ($this->repositorio->enUso('telefono', $telefono, $id)) {
                $errores['telefono'] = 'Ya hay un cliente con ese teléfono.';
            }
        }

        $puntos = filter_var($puntosTexto === '' ? '0' : $puntosTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000000]]);
        if ($puntos === false) {
            $errores['puntos'] = 'Los puntos tienen que ser un número entero, 0 o más.';
        }

        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return ['nombre' => $nombre, 'telefono' => $telefono, 'email' => $email, 'puntos' => (int) $puntos];
    }

    /** @return array<string, mixed> */
    private static function formato(array $fila): array
    {
        $telefono = (string) $fila['telefono'];
        $partes = \dividirTelefonoEnCodigoYNumero($telefono);

        return [
            'id' => (int) $fila['ID'],
            'nombre' => (string) $fila['nombre'],
            'telefono' => $telefono,
            'codigo_pais' => $partes['codigo'],
            'numero' => $partes['numero'],
            'email' => $fila['email'] !== null ? (string) $fila['email'] : null,
            'puntos' => (int) $fila['puntos'],
            'registrado' => $fila['fecha_registro'] !== null ? (string) $fila['fecha_registro'] : null,
            'cantidad_pedidos' => (int) $fila['total_pedidos'],
            'ultimo_pedido' => $fila['ultimo_pedido'] !== null ? (string) $fila['ultimo_pedido'] : null,
        ];
    }
}
