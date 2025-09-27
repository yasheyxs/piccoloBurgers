<?php
require_once __DIR__ . '/../admin/bd.php';

$categorias_disponibles = ["Acompañamientos", "Hamburguesas", "Bebidas", "Lomitos y Sándwiches", "Pizzas"];

$categoria = $_GET['categoria'] ?? '';
$busqueda = strtolower(trim($_GET['busqueda'] ?? ''));
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;

$intencion_categoria = [
  "gaseosa" => "Bebidas",
  "refresco" => "Bebidas",
  "lata" => "Bebidas",
  "botella" => "Bebidas",
  "hamburguesa" => "Hamburguesas",
  "papas" => "Acompañamientos",
  "papas fritas" => "Acompañamientos"
];

$busqueda_equivalente = [
  "cheddar" => "fritas con cheddar",
  "panceta" => "fritas con panceta",
  "latas" => "lata",
  "papas" => "fritas"
];

// Detectar equivalencias semánticas
$extra = '';
if (isset($busqueda_equivalente[$busqueda])) {
  $extra = strtolower($busqueda_equivalente[$busqueda]);
}

// Detectar intención de categoría
if (in_array(ucfirst($busqueda), $categorias_disponibles)) {
  $categoria = ucfirst($busqueda);
  $busqueda = '';
}

if (isset($intencion_categoria[$busqueda])) {
  $categoria = $intencion_categoria[$busqueda];
  $busqueda = '';
}

$excluir_lata = ($busqueda === "botella");

// Construir condiciones
$condiciones = ["m.visible_en_menu = 1"];

$parametros = [];

if ($categoria && in_array($categoria, $categorias_disponibles)) {
    $condiciones[] = "m.categoria = ?";

  $parametros[] = $categoria;
}

$busquedaCondiciones = [];
if ($busqueda !== '') {
  $busquedaCondiciones[] = "(LOWER(m.nombre) LIKE ? OR LOWER(m.ingredientes) LIKE ? OR LOWER(m.categoria) LIKE ?)";
  $parametros[] = "%$busqueda%";
  $parametros[] = "%$busqueda%";
  $parametros[] = "%$busqueda%";
}

if ($extra !== '') {
  $busquedaCondiciones[] = "(LOWER(m.nombre) LIKE ? OR LOWER(m.ingredientes) LIKE ?)";
  $parametros[] = "%$extra%";
  $parametros[] = "%$extra%";
}

if (!empty($busquedaCondiciones)) {
  $condiciones[] = "(" . implode(" OR ", $busquedaCondiciones) . ")";
}

if ($excluir_lata) {
  $condiciones[] = "LOWER(m.ingredientes) NOT LIKE ?";
  $parametros[] = "%lata%";
}

// Consulta total
$sqlTotal = "SELECT COUNT(DISTINCT m.ID) FROM tbl_menu m WHERE " . implode(" AND ", $condiciones);
$stmTotal = $conexion->prepare($sqlTotal);
$stmTotal->execute($parametros);
$totalItems = $stmTotal->fetchColumn();

// Consulta principal
$sql = "SELECT
    m.*,
    CASE
      WHEN EXISTS (
        SELECT 1
        FROM tbl_menu_materias_primas mp
        INNER JOIN tbl_materias_primas mat ON mat.ID = mp.materia_prima_id
        WHERE mp.menu_id = m.ID
          AND (
            mp.cantidad IS NULL
            OR mp.cantidad <= 0
            OR mat.cantidad < mp.cantidad
          )
      )
      THEN 0
      ELSE 1
    END AS stock_disponible
  FROM tbl_menu m
  WHERE " . implode(" AND ", $condiciones);

// Ordenamiento
if ($categoria && in_array($categoria, $categorias_disponibles)) {
  $sql .= " ORDER BY m.ID DESC";
} else {
  $case = "CASE";
  foreach ($categorias_disponibles as $i => $cat) {
    $case .= " WHEN m.categoria = " . $conexion->quote($cat) . " THEN " . ($i + 1);
  }
  $case .= " ELSE " . (count($categorias_disponibles) + 1) . " END";
  $sql .= " ORDER BY $case, m.nombre ASC";
}

$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$lista_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
foreach ($lista_menu as $registro): ?>
  <div class="col d-flex" data-aos="fade-up">
    <div class="card position-relative d-flex flex-column h-100 w-100">
      <img src="img/menu/<?= htmlspecialchars($registro["foto"]) ?>" class="card-img-top" alt="Foto de <?= htmlspecialchars($registro["nombre"]) ?>">
      <div class="card-body d-flex flex-column">
        <h5 class="card-title"><?= htmlspecialchars($registro["nombre"]) ?></h5>
        <p class="card-text small"><strong><?= htmlspecialchars($registro["ingredientes"]) ?></strong></p>
        <p class="card-text"><strong>Precio:</strong> $<?= htmlspecialchars($registro["precio"]) ?></p>
        <p class="card-text"><small><em><?= htmlspecialchars($registro["categoria"] ?? '') ?></em></small></p>
        <?php $hayStock = !empty($registro['stock_disponible']); ?>
        <button class="btn btn-agregar mt-auto<?= $hayStock ? '' : ' btn-sin-stock' ?>"<?= $hayStock ? '' : ' disabled' ?>
          data-id="<?= htmlspecialchars($registro['ID']) ?>"
          data-nombre="<?= htmlspecialchars($registro['nombre']) ?>"
          data-precio="<?= htmlspecialchars($registro['precio']) ?>"
          data-img="img/menu/<?= htmlspecialchars($registro['foto']) ?>">
          <?= $hayStock ? 'Agregar' : 'Sin stock' ?>
        </button>
      </div>
    </div>
  </div>
<?php endforeach;
$htmlContent = ob_get_clean();

header('Content-Type: application/json');
echo json_encode([
  'html' => $htmlContent,
  'totalItems' => (int) $totalItems,
]);
exit;