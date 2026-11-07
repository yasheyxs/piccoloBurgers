<?php

/**
 * Búsqueda de productos del menú. La usan el sitio (filtrar_menu.php) y la API.
 */

if (!function_exists('menuCategoriasDisponibles')) {
    /** @return string[] */
    function menuCategoriasDisponibles(): array
    {
        return ['Acompañamientos', 'Hamburguesas', 'Bebidas', 'Lomitos y Sándwiches', 'Pizzas'];
    }
}

if (!function_exists('buscarMenu')) {
    /**
     * Busca productos visibles del menú, interpretando búsquedas comunes
     * (por ejemplo "gaseosa" filtra la categoría Bebidas).
     *
     * @return array{items: array<int, array>, total: int, categoria: string, busqueda: string}
     */
    function buscarMenu(PDO $conexion, string $categoria = '', string $busqueda = '', int $offset = 0, int $limit = 8): array
    {
        $categorias = menuCategoriasDisponibles();
        $busqueda = function_exists('mb_strtolower') ? mb_strtolower(trim($busqueda)) : strtolower(trim($busqueda));
        $offset = max(0, $offset);
        $limit = max(1, $limit);

        $intencionCategoria = [
            'gaseosa' => 'Bebidas',
            'refresco' => 'Bebidas',
            'lata' => 'Bebidas',
            'botella' => 'Bebidas',
            'hamburguesa' => 'Hamburguesas',
            'papas' => 'Acompañamientos',
            'papas fritas' => 'Acompañamientos',
        ];

        $busquedaEquivalente = [
            'cheddar' => 'fritas con cheddar',
            'panceta' => 'fritas con panceta',
            'latas' => 'lata',
            'papas' => 'fritas',
        ];

        // Búsquedas que en realidad son sinónimos de otro texto
        $extra = isset($busquedaEquivalente[$busqueda]) ? strtolower($busquedaEquivalente[$busqueda]) : '';

        // Búsquedas que en realidad son una categoría
        if (in_array(ucfirst($busqueda), $categorias, true)) {
            $categoria = ucfirst($busqueda);
            $busqueda = '';
        }

        if (isset($intencionCategoria[$busqueda])) {
            $categoria = $intencionCategoria[$busqueda];
            $busqueda = '';
        }

        $excluirLata = ($busqueda === 'botella');
        $categoria = in_array($categoria, $categorias, true) ? $categoria : '';

        $condiciones = ['m.visible_en_menu = 1'];
        $parametros = [];

        if ($categoria !== '') {
            $condiciones[] = 'm.categoria = ?';
            $parametros[] = $categoria;
        }

        $condicionesBusqueda = [];
        if ($busqueda !== '') {
            $condicionesBusqueda[] = '(LOWER(m.nombre) LIKE ? OR LOWER(m.ingredientes) LIKE ? OR LOWER(m.categoria) LIKE ?)';
            array_push($parametros, "%$busqueda%", "%$busqueda%", "%$busqueda%");
        }

        if ($extra !== '') {
            $condicionesBusqueda[] = '(LOWER(m.nombre) LIKE ? OR LOWER(m.ingredientes) LIKE ?)';
            array_push($parametros, "%$extra%", "%$extra%");
        }

        if ($condicionesBusqueda) {
            $condiciones[] = '(' . implode(' OR ', $condicionesBusqueda) . ')';
        }

        if ($excluirLata) {
            $condiciones[] = 'LOWER(m.ingredientes) NOT LIKE ?';
            $parametros[] = '%lata%';
        }

        $where = implode(' AND ', $condiciones);

        $stmtTotal = $conexion->prepare("SELECT COUNT(DISTINCT m.ID) FROM tbl_menu m WHERE $where");
        $stmtTotal->execute($parametros);
        $total = (int) $stmtTotal->fetchColumn();

        if ($categoria !== '') {
            $orden = 'm.ID DESC';
        } else {
            $case = 'CASE';
            foreach ($categorias as $i => $cat) {
                $case .= ' WHEN m.categoria = ' . $conexion->quote($cat) . ' THEN ' . ($i + 1);
            }
            $case .= ' ELSE ' . (count($categorias) + 1) . ' END';
            $orden = "$case, m.nombre ASC";
        }

        $stmt = $conexion->prepare("SELECT m.* FROM tbl_menu m WHERE $where ORDER BY $orden LIMIT $limit OFFSET $offset");
        $stmt->execute($parametros);

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'categoria' => $categoria,
            'busqueda' => $busqueda,
        ];
    }
}

if (!function_exists('obtenerProductosMenuPorId')) {
    /**
     * Productos visibles del menú indexados por ID, con los datos que se cobran (nombre y precio).
     *
     * @param int[] $ids
     * @return array<int, array{ID:int, nombre:string, precio:float}>
     */
    function obtenerProductosMenuPorId(PDO $conexion, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
        if (!$ids) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conexion->prepare("SELECT ID, nombre, precio FROM tbl_menu WHERE visible_en_menu = 1 AND ID IN ($marcadores)");
        $stmt->execute($ids);

        $productos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $productos[(int) $fila['ID']] = [
                'ID' => (int) $fila['ID'],
                'nombre' => (string) $fila['nombre'],
                'precio' => (float) $fila['precio'],
            ];
        }

        return $productos;
    }
}
