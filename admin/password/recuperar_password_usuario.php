<?php
// No requiere sesión activa
?>

<?php include($_SERVER['DOCUMENT_ROOT'] . "/piccoloBurgers/admin/templates/header_public.php"); ?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h4 class="mb-4 text-center">Recuperar contraseña de usuario</h4>

      <form action="procesar_recuperacion_usuario.php" method="post">
        <div class="mb-3">
          <label for="correo" class="form-label">Correo electrónico</label>
          <input type="email" class="form-control" name="correo" id="correo" required placeholder="ejemplo@correo.com">
        </div>

        <button type="submit" class="btn btn-primary w-100">Enviar enlace de recuperación</button>
      </form>

      <div class="mt-3 text-center">
        <a href="../login.php" class="text-primary">Volver al login</a>
      </div>
    </div>
  </div>
</div>

<?php include(__DIR__ . "/../templates/footer.php"); ?>
