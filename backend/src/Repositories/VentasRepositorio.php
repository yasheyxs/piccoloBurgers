<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;

/**
 * Consultas de ventas de pedidos para los reportes. Los pedidos cancelados no cuentan como venta,
 * y el monto de cada pedido es lo que se cobró (con el descuento por puntos ya aplicado).
 * Las fechas llegan como AAAA-MM-DD y el rango incluye los dos días completos.
 */
final class VentasRepositorio
{
    private const NO_CANCELADO = "p.estado <> 'Cancelado'";

    public function __construct(private PDO $conexion)
    {
    }

    /** @return array{monto: float, pedidos: int} */
    public function totales(string $desde, string $hasta): array
    {
        $fila = $this->consultar(
            'SELECT COALESCE(SUM(p.total), 0) AS monto, COUNT(*) AS pedidos
             FROM tbl_pedidos p
             WHERE ' . self::NO_CANCELADO . ' AND p.fecha BETWEEN :desde AND :hasta',
            $desde,
            $hasta
        )[0];

        return ['monto' => (float) $fila['monto'], 'pedidos' => (int) $fila['pedidos']];
    }

    /** @return array<int, array{nombre: string, cantidad: int, monto: float}> productos vendidos, de más a menos unidades */
    public function productos(string $desde, string $hasta): array
    {
        $filas = $this->consultar(
            'SELECT pd.nombre, SUM(pd.cantidad) AS cantidad, SUM(pd.precio * pd.cantidad) AS monto
             FROM tbl_pedidos_detalle pd
             JOIN tbl_pedidos p ON pd.pedido_id = p.ID
             WHERE ' . self::NO_CANCELADO . ' AND p.fecha BETWEEN :desde AND :hasta
             GROUP BY pd.nombre
             ORDER BY cantidad DESC, pd.nombre',
            $desde,
            $hasta
        );

        return array_map(static fn(array $f): array => [
            'nombre' => (string) $f['nombre'],
            'cantidad' => (int) $f['cantidad'],
            'monto' => (float) $f['monto'],
        ], $filas);
    }

    /**
     * Pedidos y monto agrupados por una columna del pedido (método de pago o tipo de entrega).
     *
     * @param 'metodo_pago'|'tipo_entrega' $columna
     * @return array<int, array{nombre: string, pedidos: int, monto: float}>
     */
    public function agrupados(string $columna, string $desde, string $hasta): array
    {
        $columna = $columna === 'tipo_entrega' ? 'tipo_entrega' : 'metodo_pago';
        $filas = $this->consultar(
            "SELECT p.$columna AS nombre, COUNT(*) AS pedidos, COALESCE(SUM(p.total), 0) AS monto
             FROM tbl_pedidos p
             WHERE " . self::NO_CANCELADO . " AND p.fecha BETWEEN :desde AND :hasta
             GROUP BY p.$columna
             ORDER BY pedidos DESC, nombre",
            $desde,
            $hasta
        );

        return array_map(static fn(array $f): array => [
            'nombre' => (string) $f['nombre'],
            'pedidos' => (int) $f['pedidos'],
            'monto' => (float) $f['monto'],
        ], $filas);
    }

    /** @return array<int, float> monto vendido en pedidos por mes del año (1 a 12, con 0 en los meses sin ventas) */
    public function montoPorMes(int $anio): array
    {
        $meses = array_fill(1, 12, 0.0);
        $filas = $this->consultar(
            'SELECT MONTH(p.fecha) AS mes, SUM(p.total) AS monto
             FROM tbl_pedidos p
             WHERE ' . self::NO_CANCELADO . ' AND p.fecha BETWEEN :desde AND :hasta
             GROUP BY MONTH(p.fecha)',
            "$anio-01-01",
            "$anio-12-31"
        );
        foreach ($filas as $fila) {
            $meses[(int) $fila['mes']] = (float) $fila['monto'];
        }

        return $meses;
    }

    /** @return array<int, array<string, mixed>> */
    private function consultar(string $sql, string $desde, string $hasta): array
    {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
