<?php
include("../admin/bd.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$clienteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

include("../admin/templates/header.php");
?>

<br>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0">Canje de puntos</h5>
        <a href="clientes.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>
            Volver al listado
        </a>
    </div>
    <div class="card-body">
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-gift fa-2xl mb-3"></i>
            <p class="mb-0">Esta sección estará disponible próximamente.</p>
        </div>
    </div>
</div>

<?php include("../admin/templates/footer.php"); ?>