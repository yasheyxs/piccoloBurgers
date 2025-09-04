<?php
include("../bd.php");

$token = $_GET["token"] ?? "";
$tipo  = $_GET["tipo"] ?? "";

if (!in_array($tipo, ["usuario", "cliente"])) {
  $mensaje = "Tipo de cuenta inválido.";
  $mostrarFormulario = false;
} else {
  $tabla = $tipo === "usuario" ? "tbl_usuarios" : "tbl_clientes";

  $stmt = $conexion->prepare("SELECT * FROM $tabla WHERE reset_token = :token AND token_expira > NOW()");
  $stmt->bindParam(":token", $token);
  $stmt->execute();
  $registro = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$registro) {
    $mensaje = "El enlace de recuperación es inválido o ha expirado. Por favor, solicitá uno nuevo.";
    $mostrarFormulario = false;
  } else {
    $mostrarFormulario = true;
  }
}
?>

<?php include("../templates/header_public.php"); ?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">

      <?php if (!$mostrarFormulario): ?>
        <div class="alert alert-danger text-center"><?= $mensaje ?></div>
        <div class="text-center mt-3">
          <a href="recuperar_password_<?= htmlspecialchars($tipo) ?>.php" class="btn btn-warning">Volver a recuperar contraseña</a>
        </div>
      <?php else: ?>
        <h4 class="mb-4 text-center">Restablecer contraseña</h4>

        <form action="actualizar_password.php" method="post">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
          <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

          <div class="mb-3">
            <label for="nueva" class="form-label">Nueva contraseña</label>
            <input type="password" class="form-control" name="nueva" id="nueva" required
                   pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}"
                   title="Debe tener al menos 8 caracteres, incluyendo una mayúscula, una minúscula y un número.">
          </div>

          <div class="mb-3">
            <label for="confirmar" class="form-label">Confirmar contraseña</label>
            <input type="password" class="form-control" name="confirmar" id="confirmar" required>
          </div>

          <button type="submit" class="btn btn-success w-100">Actualizar contraseña</button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include("../templates/footer.php"); ?>
