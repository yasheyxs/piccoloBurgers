<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base para las pruebas que usan MySQL de verdad.
 *
 * Usan los mismos datos de conexión que el sistema (MYSQL_HOST, MYSQL_USER, etc.), pero solo
 * corren si la base se llama "..._pruebas", para no tocar nunca la base real. Preparación:
 *
 *   MYSQL_DATABASE=piccolodb_pruebas php bin/migrar.php --con-datos
 *   MYSQL_DATABASE=piccolodb_pruebas vendor/bin/phpunit --testsuite integracion
 *
 * Cada prueba crea sus propios datos (con nombres que empiezan con "Prueba ") y los borra al terminar.
 */
abstract class BaseDePruebas extends TestCase
{
    protected PDO $conexion;

    /** @var array<string, int[]> tabla => IDs creados por la prueba, para borrarlos después */
    private array $creados = [];

    protected function setUp(): void
    {
        $base = (string) piccolo_env('MYSQL_DATABASE', '');
        if (!str_ends_with($base, '_pruebas')) {
            $this->markTestSkipped('Las pruebas de integración necesitan una base que termine en "_pruebas".');
        }

        try {
            $this->conexion = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', piccolo_env('MYSQL_HOST'), piccolo_env('MYSQL_PORT') ?: '3306', $base),
                (string) piccolo_env('MYSQL_USER'),
                (string) piccolo_env('MYSQL_PASSWORD'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (\PDOException $e) {
            $this->markTestSkipped('No se pudo conectar a la base de pruebas: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (!isset($this->conexion)) {
            return;
        }
        // Se borra en orden inverso para respetar las relaciones entre tablas
        foreach (array_reverse($this->creados, true) as $tabla => $ids) {
            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            if ($tabla === 'tbl_materias_primas') {
                $this->conexion->prepare("DELETE FROM tbl_menu_materias_primas WHERE materia_prima_id IN ($marcadores)")->execute($ids);
            }
            if ($tabla === 'tbl_clientes') {
                $this->conexion->prepare("DELETE FROM movimientos_puntos WHERE cliente_id IN ($marcadores)")->execute($ids);
            }
            if ($tabla === 'tbl_pedidos') {
                $this->conexion->prepare("DELETE FROM tbl_pedidos_detalle WHERE pedido_id IN ($marcadores)")->execute($ids);
            }
            if ($tabla === 'tbl_compras') {
                $this->conexion->prepare("DELETE FROM tbl_compras_detalle WHERE compra_id IN ($marcadores)")->execute($ids);
            }
            $this->conexion->prepare("DELETE FROM $tabla WHERE ID IN ($marcadores)")->execute($ids);
        }
    }

    protected function insertar(string $tabla, array $datos): int
    {
        $columnas = implode(', ', array_keys($datos));
        $marcadores = implode(', ', array_fill(0, count($datos), '?'));
        $this->conexion->prepare("INSERT INTO $tabla ($columnas) VALUES ($marcadores)")->execute(array_values($datos));
        $id = (int) $this->conexion->lastInsertId();
        $this->creados[$tabla][] = $id;

        return $id;
    }

    /** Anota un registro creado por el código que se prueba, para borrarlo al final. */
    protected function anotar(string $tabla, int $id): void
    {
        $this->creados[$tabla][] = $id;
    }

    /**
     * Crea un producto que usa un único insumo con el stock indicado.
     *
     * @return array{producto: int, insumo: int}
     */
    protected function crearProductoConStock(float $precio, float $stockInsumo, float $insumoPorUnidad = 1): array
    {
        $proveedor = (int) $this->conexion->query('SELECT ID FROM tbl_proveedores ORDER BY ID LIMIT 1')->fetchColumn();
        $insumo = $this->insertar('tbl_materias_primas', [
            'nombre' => 'Prueba insumo', 'unidad_medida' => 'unidad', 'cantidad' => $stockInsumo,
            'proveedor_id' => $proveedor, 'unidades_por_pack' => 1, 'stock_minimo' => 0,
        ]);
        $producto = $this->insertar('tbl_menu', [
            'nombre' => 'Prueba producto', 'ingredientes' => '', 'foto' => '', 'precio' => (string) $precio,
            'categoria' => 'Hamburguesas', 'visible_en_menu' => 1,
        ]);
        $this->insertar('tbl_menu_materias_primas', [
            'menu_id' => $producto, 'materia_prima_id' => $insumo, 'cantidad' => $insumoPorUnidad,
        ]);

        return ['producto' => $producto, 'insumo' => $insumo];
    }
}
