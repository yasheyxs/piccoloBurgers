<?php
include("admin/bd.php");

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
$condiciones = ["visible_en_menu = 1"];
$parametros = [];

if ($categoria && in_array($categoria, $categorias_disponibles)) {
  $condiciones[] = "categoria = ?";
  $parametros[] = $categoria;
}

$busquedaCondiciones = [];
if ($busqueda !== '') {
  $busquedaCondiciones[] = "(LOWER(nombre) LIKE ? OR LOWER(ingredientes) LIKE ? OR LOWER(categoria) LIKE ?)";
  $parametros[] = "%$busqueda%";
  $parametros[] = "%$busqueda%";
  $parametros[] = "%$busqueda%";
}

if ($extra !== '') {
  $busquedaCondiciones[] = "(LOWER(nombre) LIKE ? OR LOWER(ingredientes) LIKE ?)";
  $parametros[] = "%$extra%";
  $parametros[] = "%$extra%";
}

if (!empty($busquedaCondiciones)) {
  $condiciones[] = "(" . implode(" OR ", $busquedaCondiciones) . ")";
}

if ($excluir_lata) {
  $condiciones[] = "LOWER(ingredientes) NOT LIKE ?";
  $parametros[] = "%lata%";
}

// Consulta total
$sqlTotal = "SELECT COUNT(DISTINCT ID) FROM tbl_menu WHERE " . implode(" AND ", $condiciones);
$stmTotal = $conexion->prepare($sqlTotal);
$stmTotal->execute($parametros);
$totalItems = $stmTotal->fetchColumn();

// Consulta principal
$sql = "SELECT DISTINCT * FROM tbl_menu WHERE " . implode(" AND ", $condiciones);

// Ordenamiento
if ($categoria && in_array($categoria, $categorias_disponibles)) {
  $sql .= " ORDER BY ID DESC";
} else {
  $case = "CASE";
  foreach ($categorias_disponibles as $i => $cat) {
    $case .= " WHEN categoria = " . $conexion->quote($cat) . " THEN " . ($i + 1);
  }
  $case .= " ELSE " . (count($categorias_disponibles) + 1) . " END";
  $sql .= " ORDER BY $case, nombre ASC";
}

$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$lista_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Tarjetas: se insertan en #contenedor-menu -->
<?php foreach ($lista_menu as $registro): ?>
  <div class="col d-flex" data-aos="fade-up">
    <div class="card position-relative d-flex flex-column h-100 w-100">
      <img src="img/menu/<?= htmlspecialchars($registro["foto"]) ?>" class="card-img-top" alt="Foto de <?= htmlspecialchars($registro["nombre"]) ?>">
      <div class="card-body d-flex flex-column">
        <h5 class="card-title"><?= htmlspecialchars($registro["nombre"]) ?></h5>
        <p class="card-text small"><strong><?= htmlspecialchars($registro["ingredientes"]) ?></strong></p>
        <p class="card-text"><strong>Precio:</strong> $<?= htmlspecialchars($registro["precio"]) ?></p>
        <p class="card-text"><small><em><?= htmlspecialchars($registro["categoria"] ?? '') ?></em></small></p>
        <button class="btn btn-agregar mt-auto"
          data-id="<?= htmlspecialchars($registro['ID']) ?>"
          data-nombre="<?= htmlspecialchars($registro['nombre']) ?>"
          data-precio="<?= htmlspecialchars($registro['precio']) ?>"
          data-img="img/menu/<?= htmlspecialchars($registro['foto']) ?>">
          Agregar
        </button>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<!-- Botón se insertará en #contenedor-boton-mas por el JS -->
<?php if (($offset + count($lista_menu)) < $totalItems): ?>
  <div id="btn-mostrar-mas-wrapper">
    <button id="btn-mostrar-mas" class="btn btn-gold">Mostrar más</button>
  </div>
<?php endif; ?>
