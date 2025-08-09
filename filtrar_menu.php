<?php
include("admin/bd.php");

$categorias_disponibles = ["Acompañamientos", "Hamburguesas", "Bebidas", "Lomitos y Sándwiches", "Pizzas"];

$categoria = $_GET['categoria'] ?? '';
$busqueda = trim($_GET['busqueda'] ?? '');

$parametros = [];
$sql = "SELECT * FROM tbl_menu WHERE 1=1";

// 1) WHERE filters (primero)
if ($categoria && in_array($categoria, $categorias_disponibles)) {
    $sql .= " AND categoria = ?";
    $parametros[] = $categoria;
}

if ($busqueda !== '') {
    $sql .= " AND nombre LIKE ?";
    $parametros[] = "%$busqueda%";
}

// 2) ORDER BY (siempre al final)
if ($categoria && in_array($categoria, $categorias_disponibles)) {
    // Si hay filtro de categoría, orden cronológico (más nuevo primero)
    $sql .= " ORDER BY ID DESC";
} else {
    // Si no hay filtro, usamos un ORDER BY que respete tu orden manual de categorías.
    // Construimos un CASE para compatibilidad entre motores SQL (no depende de FIELD()).
    $case = "CASE";
    foreach ($categorias_disponibles as $i => $cat) {
        // $conexion->quote() pone las comillas correctamente y evita inyección
        $case .= " WHEN categoria = " . $conexion->quote($cat) . " THEN " . ($i + 1);
    }
    $case .= " ELSE " . (count($categorias_disponibles) + 1) . " END";
    $sql .= " ORDER BY $case, nombre ASC";
}

$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$lista_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Renderizamos resultados. Usamos htmlspecialchars para evitar XSS.
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

<?php if (count($lista_menu) === 0): ?>
  <div class="d-flex justify-content-center align-items-center mt-5 w-100">
    <div style="
      background-color: #3a2a00;
      color: #fac30c;
      border: 1px solid #fac30c;
      font-size: 1.3rem;
      font-weight: bold;
      border-radius: 12px;
      padding: 1.5rem 2rem;
      max-width: 700px;
      width: 100%;
      text-align: center;
      display: inline-block;">
      No se encontraron productos que coincidan con tu búsqueda.
    </div>
  </div>
<?php endif; ?>
