<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;
use Piccolo\Http\Api\ApiException;
use Piccolo\Http\Api\Formato;
use Piccolo\Repositories\CanjeRepositorio;
use Piccolo\Repositories\ClientesRepositorio;

/**
 * Canje de puntos de un cliente desde el panel, de dos formas:
 * - una venta con descuento: se registra como un pedido más, con las mismas reglas que los pedidos del sitio
 *   (crearPedido), pero usando la cantidad de puntos que elige quien atiende;
 * - un canje de premios: descuenta el costo en puntos de los premios elegidos.
 * Lo usan la pantalla de canje y la API (/api/v1/admin/clientes/{id}/canje).
 */
final class CanjeController
{
    private const NO_EXISTE = 'El cliente no existe o ya fue eliminado.';

    public function __construct(
        private ClientesRepositorio $clientes,
        private CanjeRepositorio $repositorio,
        private PDO $conexion
    ) {
        require_once \piccolo_path('src/Services/pedidos.php');
        require_once \piccolo_path('src/Services/puntos_config.php');
        require_once \piccolo_path('src/Services/reservas_virtuales.php');
        require_once \piccolo_path('src/Http/Admin/telefonos.php');
    }

    /**
     * Lo que necesita la pantalla de canje: el cliente, las reglas de puntos, los productos y los premios.
     *
     * @return array<string, mixed>
     */
    public function datos(int $clienteId): array
    {
        $cliente = $this->cliente($clienteId);
        $config = \obtenerConfiguracionPuntos($this->conexion);

        return [
            'cliente' => [
                'id' => (int) $cliente['ID'],
                'nombre' => (string) $cliente['nombre'],
                'telefono' => (string) $cliente['telefono'],
                'email' => $cliente['email'] !== null ? (string) $cliente['email'] : null,
                'puntos' => (int) $cliente['puntos'],
            ],
            'config' => [
                'minimo_puntos' => (int) $config['minimo_puntos'],
                'valor_punto' => (float) $config['valor_punto'],
                'maximo_porcentaje' => (float) $config['maximo_porcentaje'],
            ],
            'metodos_pago' => \pedidoMetodosPago(),
            'tipos_entrega' => \pedidoTiposEntrega(),
            'menu' => array_map(static function (array $p): array {
                $foto = trim((string) ($p['foto'] ?? ''));
                return [
                    'id' => (int) $p['ID'],
                    'nombre' => (string) $p['nombre'],
                    'precio' => (float) $p['precio'],
                    'categoria' => (string) ($p['categoria'] ?: 'General'),
                    'imagen' => $foto !== '' ? Formato::urlPublica('img/menu/' . rawurlencode($foto)) : null,
                ];
            }, $this->repositorio->productos()),
            'premios' => array_map(static function (array $p): array {
                $imagen = trim((string) ($p['imagen'] ?? ''));
                return [
                    'id' => (int) $p['id'],
                    'nombre' => (string) $p['nombre'],
                    'descripcion' => $p['descripcion'] !== null ? (string) $p['descripcion'] : null,
                    'costo_puntos' => (int) $p['costo_puntos'],
                    'imagen' => $imagen !== '' ? Formato::urlPublica(preg_match('#^https?://#i', $imagen) ? $imagen : 'img/premios/' . rawurlencode($imagen)) : null,
                ];
            }, $this->repositorio->premios()),
        ];
    }

    /**
     * Registra una venta para el cliente, con descuento por los puntos elegidos.
     *
     * @param array<string, mixed> $datos items [{id, cantidad}], puntos_utilizados, metodo_pago, tipo_entrega, direccion, referencias, nota
     * @param string|null $sesion sesión cuyas reservas de stock se liberan después de vender (las que armó la pantalla)
     * @return array<string, mixed>
     */
    public function registrarVenta(int $clienteId, array $datos, ?string $sesion = null): array
    {
        $cliente = $this->cliente($clienteId);
        $telefono = \dividirTelefonoEnCodigoYNumero((string) $cliente['telefono']);
        if (\validarTelefono($telefono['codigo'], $telefono['numero']) === false) {
            throw ApiException::validacion(['telefono' => 'El teléfono del cliente no es válido. Corregilo en "Editar cliente" antes de registrar la venta.']);
        }

        $puntos = filter_var(Datos::texto($datos['puntos_utilizados'] ?? '0') ?: '0', FILTER_VALIDATE_INT);
        if ($puntos === false || $puntos < 0) {
            throw ApiException::validacion(['puntos_utilizados' => 'Ingresá una cantidad de puntos válida.']);
        }

        $nota = Datos::texto($datos['nota'] ?? '');
        $nota = mb_substr($nota !== '' ? $nota : ($puntos > 0
            ? 'Venta registrada desde el panel con canje de puntos.'
            : 'Venta registrada desde el panel.'), 0, 500);

        try {
            $pedido = \crearPedido($this->conexion, [
                'nombre' => (string) $cliente['nombre'],
                'codigo_pais' => $telefono['codigo'],
                'telefono' => $telefono['numero'],
                'email' => (string) ($cliente['email'] ?? ''),
                'nota' => $nota,
                'metodo_pago' => Datos::texto($datos['metodo_pago'] ?? ''),
                'tipo_entrega' => Datos::texto($datos['tipo_entrega'] ?? ''),
                'direccion' => mb_substr(Datos::texto($datos['direccion'] ?? ''), 0, 255),
                'referencias' => Datos::texto($datos['referencias'] ?? ''),
                'items' => is_array($datos['items'] ?? null) ? $datos['items'] : [],
            ], $clienteId, $puntos);
        } catch (\PedidoException $error) {
            throw ApiException::validacion([], $error->getMessage());
        }

        if ($sesion !== null) {
            try {
                \limpiarReservasSesion($this->conexion, $sesion);
            } catch (\Throwable $error) {
                error_log("No se pudieron liberar las reservas tras la venta {$pedido['pedido_id']}: " . $error->getMessage());
            }
        }

        return [
            'mensaje' => 'La venta se registró correctamente.',
            'pedido_id' => $pedido['pedido_id'],
            'items' => $pedido['items'],
            'total_original' => $pedido['total_original'],
            'descuento' => $pedido['descuento'],
            'total_final' => $pedido['total'],
            'puntos_usados' => $pedido['puntos_usados'],
            'puntos_ganados' => $pedido['puntos_ganados'],
            'puntos_actuales' => (int) $this->cliente($clienteId)['puntos'],
            'metodo_pago' => Datos::texto($datos['metodo_pago'] ?? ''),
            'tipo_entrega' => Datos::texto($datos['tipo_entrega'] ?? ''),
        ];
    }

    /**
     * Canjea premios por puntos.
     *
     * @param array<string, mixed> $datos premios [{id, cantidad}]
     * @return array<string, mixed>
     */
    public function canjearPremios(int $clienteId, array $datos): array
    {
        $this->cliente($clienteId);

        $cantidades = [];
        foreach (is_array($datos['premios'] ?? null) ? $datos['premios'] : [] as $item) {
            $id = is_array($item) ? Datos::id($item['id'] ?? 0) : 0;
            $cantidad = is_array($item) ? Datos::id($item['cantidad'] ?? 0) : 0;
            if ($id <= 0 || $cantidad <= 0 || $cantidad > 100) {
                throw ApiException::validacion(['premios' => 'Hay un premio o una cantidad que no es válida.']);
            }
            $cantidades[$id] = ($cantidades[$id] ?? 0) + $cantidad;
        }
        if (!$cantidades) {
            throw ApiException::validacion(['premios' => 'Elegí al menos un premio.']);
        }

        $premios = $this->repositorio->premiosPorId(array_keys($cantidades));
        $detalle = [];
        $total = 0;
        foreach ($cantidades as $id => $cantidad) {
            $premio = $premios[$id] ?? null;
            if ($premio === null || (int) $premio['costo_puntos'] <= 0) {
                throw ApiException::validacion(['premios' => 'Uno de los premios ya no está disponible.']);
            }
            $costo = (int) $premio['costo_puntos'];
            $total += $costo * $cantidad;
            $detalle[] = ['id' => $id, 'nombre' => (string) $premio['nombre'], 'cantidad' => $cantidad, 'costo_puntos' => $costo, 'total_puntos' => $costo * $cantidad];
        }

        $descripcion = mb_substr('Canje de premios: ' . implode(', ', array_map(
            static fn(array $d): string => "{$d['cantidad']}x {$d['nombre']}",
            $detalle
        )), 0, 255);

        $saldo = $this->repositorio->canjearPremios($clienteId, $total, $descripcion);
        if ($saldo === null) {
            throw ApiException::validacion(['premios' => 'El cliente no tiene puntos suficientes para ese canje.']);
        }

        return [
            'mensaje' => 'El canje de premios se registró correctamente.',
            'puntos_usados' => $total,
            'puntos_actuales' => $saldo,
            'puntos_restantes' => $saldo,
            'detalle' => $detalle,
            'descripcion' => $descripcion,
            'fecha' => date('c'),
        ];
    }

    /** @return array<string, mixed> */
    private function cliente(int $id): array
    {
        $cliente = $this->clientes->buscar($id);
        if ($cliente === null) {
            throw ApiException::noEncontrado(self::NO_EXISTE);
        }

        return $cliente;
    }
}
