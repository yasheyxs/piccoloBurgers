<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
piccolo_admin_iniciar_sesion();
if ($_POST) {
  include(__DIR__ . "/../../src/Core/database.php");
  require_once __DIR__ . '/../../src/Services/Security/login_panel.php';

  $resultado = piccoloPanelAutenticar($conexion, (string) ($_POST["usuario"] ?? ""), (string) ($_POST["password"] ?? ""));

  if ($resultado['usuario'] !== null) {
    piccolo_admin_guardar_sesion($resultado['usuario']);

    // En las pantallas del panel la cookie de sesión no viaja desde otros sitios
    $parametrosCookie = session_get_cookie_params();
    setcookie(session_name(), session_id(), [
      'expires' => 0,
      'path' => $parametrosCookie['path'],
      'domain' => $parametrosCookie['domain'],
      'secure' => piccolo_detect_scheme() === 'https',
      'httponly' => true,
      'samesite' => 'Strict'
    ]);

    header("Location:index.php");
    exit();
  }

  $mensaje = piccoloPanelMensajeRechazo($resultado);
}
?>


<!doctype html>
<html lang="es">

<head>
  <title>Login Administrador</title>
  <link rel="icon" type="image/png" href="../img/favicon.png" />
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

  <main>
    <div class="container">
      <div class="row justify-content-center mt-5">
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">

          <?php if (isset($mensaje)) { ?>
            <div class="alert alert-danger text-center">
              <strong>Error:</strong> <?php echo $mensaje; ?>
            </div>
          <?php } ?>

          <div class="card shadow-sm">
            <div class="card-header text-center fw-bold">Acceso al administrador</div>
            <div class="card-body p-4">
              <form action="login.php" method="post">
                <div class="mb-3">
                  <label for="usuario" class="form-label">Usuario</label>
                  <input type="text" class="form-control" name="usuario" id="usuario" required>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Clave</label>
                  <input type="password" class="form-control" name="password" id="password" required>
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
              </form>

              <div class="mt-3 text-center">
                <a href="password/recuperar_password_usuario.php?tipo=usuario" class="text-primary">¿Olvidaste tu contraseña?</a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
