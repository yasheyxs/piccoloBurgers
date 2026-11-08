<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;

/**
 * Consultas a la base de las bebidas (catálogo) y de sus ventas en la jornada (bebida_ventas).
 * Una venta es una jarra o una promoción (varias jarras) para una persona; se va entregando
 * jarra por jarra. Los resúmenes y estadísticas siguen en src/Services/bebidas_schema.php.
 */
final class BebidasRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array<string, mixed>> todas las bebidas, primero las activas */
    public function catalogo(): array
    {
        return $this->conexion->query('SELECT * FROM bebidas ORDER BY activa DESC, nombre')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function bebida(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT * FROM bebidas WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function nombreEnUso(string $nombre, int $exceptoId = 0): bool
    {
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM bebidas WHERE nombre = ? AND id <> ?');
        $stmt->execute([$nombre, $exceptoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @param array{nombre: string, valor_jarra: float, valor_promo: float, jarras_por_promo: int, activa: bool} $datos */
    public function guardarBebida(int $id, array $datos): int
    {
        $valores = [$datos['nombre'], $datos['valor_jarra'], $datos['valor_promo'], $datos['jarras_por_promo'], $datos['activa'] ? 1 : 0];
        if ($id > 0) {
            $this->conexion->prepare('UPDATE bebidas SET nombre = ?, valor_jarra = ?, valor_promo = ?, jarras_por_promo = ?, activa = ? WHERE id = ?')
                ->execute([...$valores, $id]);

            return $id;
        }
        $this->conexion->prepare('INSERT INTO bebidas (nombre, valor_jarra, valor_promo, jarras_por_promo, activa) VALUES (?, ?, ?, ?, ?)')
            ->execute($valores);

        return (int) $this->conexion->lastInsertId();
    }

    /** @return array<string, mixed>|null la venta, si es de esa jornada */
    public function venta(int $id, int $jornadaId): ?array
    {
        $stmt = $this->conexion->prepare('SELECT * FROM bebida_ventas WHERE id = ? AND jornada_id = ?');
        $stmt->execute([$id, $jornadaId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Guarda la venta con el nombre y el precio del momento, así el historial no cambia si después
     * se modifica la bebida.
     */
    public function registrarVenta(int $jornadaId, array $bebida, string $persona, string $tipo, int $cantidad): void
    {
        $promo = $tipo === 'promo';
        $this->conexion->prepare(
            'INSERT INTO bebida_ventas (jornada_id, bebida_id, bebida_nombre, persona, tipo, cantidad_total, cantidad_entregada, unidades_por_item, valor_unitario)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)'
        )->execute([
            $jornadaId, (int) $bebida['id'], $bebida['nombre'], $persona, $tipo, $cantidad,
            $promo ? max(1, (int) $bebida['jarras_por_promo']) : 1,
            $promo ? (float) $bebida['valor_promo'] : (float) $bebida['valor_jarra'],
        ]);
    }

    public function renombrar(int $id, string $persona): void
    {
        $this->conexion->prepare('UPDATE bebida_ventas SET persona = ? WHERE id = ?')->execute([$persona, $id]);
    }

    /** Suma (+1) o resta (-1) una jarra entregada, sin pasarse del total ni bajar de cero. */
    public function cambiarEntregadas(int $id, int $cambio): void
    {
        $sql = $cambio > 0
            ? 'UPDATE bebida_ventas SET cantidad_entregada = LEAST(cantidad_total * unidades_por_item, cantidad_entregada + 1) WHERE id = ?'
            : 'UPDATE bebida_ventas SET cantidad_entregada = GREATEST(0, cantidad_entregada - 1) WHERE id = ?';
        $this->conexion->prepare($sql)->execute([$id]);
    }
}
