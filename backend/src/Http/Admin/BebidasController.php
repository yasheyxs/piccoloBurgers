<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\BebidasRepositorio;

/**
 * Reglas de bebidas: la venta y entrega de jarras durante la jornada, y el catálogo de bebidas.
 * La jornada es la misma que la del pool, así que abrirla y cerrarla lo hace PoolController.
 * La usan la pantalla de bebidas (por seccion/bebidas/api.php), la de parámetros y la API
 * (/api/v1/admin/bebidas).
 *
 * Como en el pool, los montos y cantidades vendidas no se muestran sin confirmar la contraseña.
 */
final class BebidasController
{
    public const TIPOS = ['jarra' => 'Jarra', 'promo' => 'Promoción'];
    private const NO_EXISTE = 'No encontramos esa venta en la jornada activa.';

    public function __construct(
        private BebidasRepositorio $repositorio,
        private PoolController $pool,
        private PDO $conexion
    ) {
        require_once \piccolo_path('src/Services/bebidas_schema.php');
    }

    /** @return array<string, mixed> jornada, bebidas activas, resumen y ventas pendientes de entregar */
    public function estado(bool $conSensibles = false): array
    {
        $estado = \bebidaObtenerEstado($this->conexion);
        if (!$conSensibles) {
            foreach (['vendidas', 'entregadas', 'montoVendido'] as $dato) {
                $estado['stats'][$dato] = null;
            }
            foreach ($estado['bebidas'] as &$bebida) {
                $bebida['valor_jarra'] = $bebida['valor_promo'] = null;
            }
            unset($bebida);
            foreach ($estado['ventas'] as &$venta) {
                $venta['cantidad_total'] = $venta['cantidad_entregada'] = $venta['valor_unitario'] = null;
            }
            unset($venta);
        }

        return $estado;
    }

    /** @return array<string, mixed> el estado completo */
    public function mostrarSensibles(int $usuarioId, string $password): array
    {
        DatosSensibles::confirmar($this->conexion, $usuarioId, $password);

        return $this->estado(true);
    }

    public function abrirJornada(): void
    {
        $this->pool->abrirJornada();
    }

    /** @return array{fichasVendidas: int, bebidasVendidas: int} */
    public function totalesCierre(): array
    {
        return $this->pool->totalesCierre();
    }

    public function cerrarJornada(array $totales = []): void
    {
        $this->pool->cerrarJornada($totales);
    }

    /** @param array<string, mixed> $datos bebidaId (o bebida_id), persona, tipo (jarra o promo), cantidad */
    public function vender(array $datos): void
    {
        $jornada = $this->jornadaId();
        $errores = [];
        $bebida = $this->repositorio->bebida(Datos::id($datos['bebida_id'] ?? ($datos['bebidaId'] ?? 0)));
        if ($bebida === null || !(bool) $bebida['activa']) {
            $errores['bebida_id'] = 'Elegí una bebida disponible.';
        }
        $persona = Datos::texto($datos['persona'] ?? '');
        if ($persona === '' || mb_strlen($persona) > 120) {
            $errores['persona'] = 'Ingresá un nombre o identificación (hasta 120 caracteres).';
        }
        $tipo = Datos::texto($datos['tipo'] ?? 'jarra');
        if (!isset(self::TIPOS[$tipo])) {
            $errores['tipo'] = 'El tipo tiene que ser "jarra" o "promo".';
        }
        $cantidad = filter_var(Datos::texto($datos['cantidad'] ?? ''), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        if ($cantidad === false) {
            $errores['cantidad'] = 'Ingresá una cantidad entre 1 y 99.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $this->repositorio->registrarVenta($jornada, (array) $bebida, $persona, $tipo, (int) $cantidad);
    }

    public function renombrar(int $id, array $datos): void
    {
        $venta = $this->venta($id);
        $persona = Datos::texto($datos['nombre'] ?? ($datos['persona'] ?? ''));
        if ($persona === '' || mb_strlen($persona) > 120) {
            throw ApiException::validacion(['nombre' => 'Ingresá un nombre o identificación (hasta 120 caracteres).']);
        }
        $this->repositorio->renombrar((int) $venta['id'], $persona);
    }

    /** Anota una jarra entregada. */
    public function entregar(int $id): void
    {
        $venta = $this->venta($id);
        if ((int) $venta['cantidad_entregada'] >= (int) $venta['cantidad_total'] * max(1, (int) $venta['unidades_por_item'])) {
            throw new ApiException('Esta venta ya se entregó completa.', 409);
        }
        $this->repositorio->cambiarEntregadas($id, +1);
    }

    /** Deshace una entrega anotada por error. */
    public function revertir(int $id): void
    {
        $venta = $this->venta($id);
        if ((int) $venta['cantidad_entregada'] <= 0) {
            throw new ApiException('Esta venta todavía no tiene entregas.', 409);
        }
        $this->repositorio->cambiarEntregadas($id, -1);
    }

    /** @return array<int, array{id: int, nombre: string, valor_jarra: float, valor_promo: float, jarras_por_promo: int, activa: bool}> */
    public function catalogo(): array
    {
        return array_map([self::class, 'formatoBebida'], $this->repositorio->catalogo());
    }

    /**
     * Crea (sin $id) o modifica una bebida del catálogo. Una bebida inactiva no se ofrece para vender,
     * pero sus ventas anteriores siguen en las estadísticas.
     *
     * @param array<string, mixed> $datos nombre, valor_jarra, valor_promo, jarras_por_promo, activa
     * @return array{id: int, nombre: string, valor_jarra: float, valor_promo: float, jarras_por_promo: int, activa: bool}
     */
    public function guardarBebida(array $datos, int $id = 0): array
    {
        if ($id > 0 && $this->repositorio->bebida($id) === null) {
            throw ApiException::noEncontrado('La bebida no existe.');
        }

        $nombre = Datos::texto($datos['nombre'] ?? '');
        $jarra = Datos::decimal($datos['valor_jarra'] ?? null);
        $promo = Datos::decimal($datos['valor_promo'] ?? null);
        $jarrasPorPromo = filter_var(Datos::texto($datos['jarras_por_promo'] ?? ''), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        $errores = [];

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'Ingresá el nombre de la bebida (hasta 100 caracteres).';
        } elseif ($this->repositorio->nombreEnUso($nombre, $id)) {
            $errores['nombre'] = 'Ya hay una bebida con ese nombre.';
        }
        if ($jarra === null || $jarra <= 0) {
            $errores['valor_jarra'] = 'El valor de la jarra tiene que ser mayor a 0.';
        }
        if ($promo === null || $promo <= 0) {
            $errores['valor_promo'] = 'El valor de la promoción tiene que ser mayor a 0.';
        }
        if ($jarrasPorPromo === false) {
            $errores['jarras_por_promo'] = 'Las jarras por promoción tienen que ser entre 1 y 99.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $id = $this->repositorio->guardarBebida($id, [
            'nombre' => $nombre,
            'valor_jarra' => round((float) $jarra, 2),
            'valor_promo' => round((float) $promo, 2),
            'jarras_por_promo' => (int) $jarrasPorPromo,
            'activa' => filter_var($datos['activa'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        return self::formatoBebida((array) $this->repositorio->bebida($id));
    }

    private function jornadaId(): int
    {
        $jornada = \poolJornadaActiva($this->conexion);
        if (!$jornada) {
            throw new ApiException('Abrí una jornada antes de registrar ventas.', 409);
        }

        return (int) $jornada['id'];
    }

    /** @return array<string, mixed> */
    private function venta(int $id): array
    {
        $venta = $id > 0 ? $this->repositorio->venta($id, $this->jornadaId()) : null;
        if ($venta === null) {
            throw ApiException::noEncontrado(self::NO_EXISTE);
        }

        return $venta;
    }

    /** @return array{id: int, nombre: string, valor_jarra: float, valor_promo: float, jarras_por_promo: int, activa: bool} */
    private static function formatoBebida(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'valor_jarra' => (float) $fila['valor_jarra'],
            'valor_promo' => (float) $fila['valor_promo'],
            'jarras_por_promo' => max(1, (int) ($fila['jarras_por_promo'] ?? 1)),
            'activa' => (bool) $fila['activa'],
        ];
    }
}
