<?php
include("../../bd.php");
session_start();

// Solo admin puede acceder
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

// Eliminar usuario si se pasa txtID
if (isset($_GET["txtID"])) {
    $txtID = intval($_GET["txtID"]);

    // Verificar si el usuario a eliminar es admin
    $stmt = $conexion->prepare("SELECT rol FROM tbl_usuarios WHERE ID = :id");
    $stmt->bindParam(":id", $txtID);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $usuario["rol"] === "admin") {
        // Contar admins
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE rol = 'admin'");
        $stmt->execute();
        $totalAdmins = $stmt->fetchColumn();

        if ($totalAdmins <= 1) {
            echo "<script>alert('No se puede eliminar el último administrador'); window.location.href='index.php';</script>";
            exit();
        }
    }

    // Ejecutar eliminación
    $stmt = $conexion->prepare("DELETE FROM tbl_usuarios WHERE ID = :id");
    $stmt->bindParam(":id", $txtID);
    $stmt->execute();

    header("Location:index.php");
    exit();
}

// Obtener lista de usuarios
$sentencia = $conexion->prepare("SELECT * FROM tbl_usuarios");
$sentencia->execute();
$lista_usuarios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../../templates/header.php");
?>

<br>
<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar registros</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaUsuarios" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_usuarios as $registro) { ?>
            <tr>
              <td><?php echo $registro["ID"]; ?></td>
              <td><?php echo $registro["usuario"]; ?></td>
              <td><?php echo $registro["correo"]; ?></td>
              <td><?php echo ucfirst($registro["rol"]); ?></td>
              <td>
  <?php
    $mostrarBoton = true;

    if ($registro["rol"] === "admin") {
      // Contar admins
      $stmt = $conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE rol = 'admin'");
      $stmt->execute();
      $totalAdmins = $stmt->fetchColumn();

      // Si es el último admin, no mostrar botón
      if ($totalAdmins <= 1) {
        $mostrarBoton = false;
      }
    }

    if ($mostrarBoton) {
  ?>
    <a class="btn btn-danger btn-sm" href="index.php?txtID=<?php echo $registro["ID"]; ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?')">Borrar</a>
  <?php } else { ?>
    <span class="text-muted">Protegido</span>
  <?php } ?>
</td>

            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer text-muted"></div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    initDataTable('#tablaUsuarios');
  });
</script>

<?php include("../../templates/footer.php"); ?>
