<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;
use Throwable;

/**
 * Consultas a la base de los turnos del pool (pool_turnos). Cada turno es una persona en la fila
 * de una mesa (azul o roja) con las fichas que compró. Las jornadas y los resúmenes siguen en
 * src/Services/pool_schema.php, porque también los usan bebidas y ventas.
 */
final class PoolRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<string, mixed>|null el turno, si es de esa jornada */
    public function turno(int $id, int $jornadaId): ?array
    {
        $stmt = $this->conexion->prepare('SELECT * FROM pool_turnos WHERE id = ? AND jornada_id = ?');
        $stmt->execute([$id, $jornadaId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Anota fichas compradas. Si superan el máximo por registro se reparten en varios turnos seguidos
     * (por ejemplo, 5 fichas con un máximo de 3 son un turno de 3 y otro de 2).
     *
     * @return int cantidad de turnos creados
     */
    public function agregar(int $jornadaId, string $pool, string $nombre, int $fichas, int $maximoPorTurno, float $valorFicha): int
    {
        return $this->enTransaccion(function () use ($jornadaId, $pool, $nombre, $fichas, $maximoPorTurno, $valorFicha): int {
            $orden = $this->siguienteOrden($jornadaId, $pool);
            $insertar = $this->conexion->prepare(
                'INSERT INTO pool_turnos (jornada_id, pool, nombre, fichas_total, fichas_consumidas, valor_ficha, orden, jornada_fecha)
                 VALUES (?, ?, ?, ?, 0, ?, ?, ?)'
            );
            $turnos = 0;
            while ($fichas > 0) {
                $lote = min($maximoPorTurno, $fichas);
                $insertar->execute([$jornadaId, $pool, $nombre, $lote, $valorFicha, $orden++, date('Y-m-d')]);
                $fichas -= $lote;
                $turnos++;
            }

            return $turnos;
        });
    }

    public function renombrar(int $id, string $nombre): void
    {
        $this->conexion->prepare('UPDATE pool_turnos SET nombre = ? WHERE id = ?')->execute([$nombre, $id]);
    }

    /** Suma (+1) o resta (-1) una ficha usada, sin pasarse de las que compró ni bajar de cero. */
    public function cambiarFichasUsadas(int $id, int $cambio): void
    {
        $sql = $cambio > 0
            ? 'UPDATE pool_turnos SET fichas_consumidas = LEAST(fichas_total, fichas_consumidas + 1) WHERE id = ?'
            : 'UPDATE pool_turnos SET fichas_consumidas = GREATEST(0, fichas_consumidas - 1) WHERE id = ?';
        $this->conexion->prepare($sql)->execute([$id]);
    }

    /** Pasa el turno al final de la fila de la otra mesa. */
    public function transferir(int $id, int $jornadaId, string $pool): void
    {
        $this->conexion->prepare('UPDATE pool_turnos SET pool = ?, orden = ? WHERE id = ?')
            ->execute([$pool, $this->siguienteOrden($jornadaId, $pool), $id]);
    }

    /** Intercambia el lugar con el turno pendiente de arriba o de abajo. */
    public function mover(array $turno, string $direccion): void
    {
        $arriba = $direccion === 'arriba';
        $stmt = $this->conexion->prepare(
            'SELECT id, orden FROM pool_turnos
             WHERE pool = ? AND jornada_id = ? AND fichas_consumidas < fichas_total AND orden ' . ($arriba ? '<' : '>') . ' ?
             ORDER BY orden ' . ($arriba ? 'DESC' : 'ASC') . ', id ' . ($arriba ? 'DESC' : 'ASC') . ' LIMIT 1'
        );
        $stmt->execute([$turno['pool'], (int) $turno['jornada_id'], (int) $turno['orden']]);
        $vecino = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$vecino) {
            return;
        }

        $this->enTransaccion(function () use ($turno, $vecino): void {
            $actualizar = $this->conexion->prepare('UPDATE pool_turnos SET orden = ? WHERE id = ?');
            $actualizar->execute([(int) $vecino['orden'], (int) $turno['id']]);
            $actualizar->execute([(int) $turno['orden'], (int) $vecino['id']]);
        });
    }

    /** @param int[] $ids turnos de la mesa en el orden nuevo (los que no son de esa mesa y jornada se ignoran) */
    public function reordenar(int $jornadaId, string $pool, array $ids): void
    {
        $this->enTransaccion(function () use ($jornadaId, $pool, $ids): void {
            $actualizar = $this->conexion->prepare('UPDATE pool_turnos SET orden = ? WHERE id = ? AND pool = ? AND jornada_id = ?');
            foreach (array_values($ids) as $i => $id) {
                $actualizar->execute([$i + 1, $id, $pool, $jornadaId]);
            }
        });
    }

    private function siguienteOrden(int $jornadaId, string $pool): int
    {
        $stmt = $this->conexion->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM pool_turnos WHERE pool = ? AND jornada_id = ?');
        $stmt->execute([$pool, $jornadaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @template T
     * @param callable(): T $trabajo
     * @return T
     */
    private function enTransaccion(callable $trabajo): mixed
    {
        $this->conexion->beginTransaction();
        try {
            $resultado = $trabajo();
            $this->conexion->commit();

            return $resultado;
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            throw $error;
        }
    }
}
