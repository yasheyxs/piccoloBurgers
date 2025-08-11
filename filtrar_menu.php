<?php
include("admin/bd.php");

$categorias_disponibles = ["Acompañamientos", "Hamburguesas", "Bebidas", "Lomitos y Sándwiches", "Pizzas"];

$categoria = $_GET['categoria'] ?? '';
$busqueda = trim($_GET['busqueda'] ?? '');
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8; // 8 por carga

// ----------- Contar total de ítems -----------
$sqlTotal = "SELECT COUNT(*) FROM tbl_menu WHERE 1=1";
$paramsTotal = [];

if ($categoria && in_array($categoria, $categorias_disponibles)) {
    $sqlTotal .= " AND categoria = ?";
    $paramsTotal[] = $categoria;
}

if ($busqueda !== '') {
    $sqlTotal .= " AND nombre LIKE ?";
    $paramsTotal[] = "%$busqueda%";
}

$stmTotal = $conexion->prepare($sqlTotal);
$stmTotal->execute($paramsTotal);
$totalItems = $stmTotal->fetchColumn();

// ----------- Construir query principal -----------
$sql = "SELECT * FROM tbl_menu WHERE 1=1";
$parametros = [];

if ($categoria && in_array($categoria, $categorias_disponibles)) {
    $sql .= " AND categoria = ?";
    $parametros[] = $categoria;
}

if ($busqueda !== '') {
    $sql .= " AND nombre LIKE ?";
    $parametros[] = "%$busqueda%";
}

// Orden
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

// Límite y desplazamiento
$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$lista_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si ya se han cargado todos los productos posibles en esta carga
if (($offset + count($lista_menu)) >= $totalItems) {
  echo '<div id="ultima-carga" style="display:none;"></div>';
}


// Mostrar mensaje si no hay resultados
if (count($lista_menu) < $limit) {
    echo '<div id="ultima-carga" style="display:none;"></div>';
}


// Render HTML parcial
foreach ($lista_menu as $registro): ?>
  <div class="col d-flex"
       data-aos-up="fade-up"
       data-aos-down="fade-down"
       data-aos="fade-up">
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
