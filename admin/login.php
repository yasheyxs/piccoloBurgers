<?php
session_start();
if ($_POST) {
  include("bd.php");
  require_once __DIR__ . '/helpers/url.php';
  require_once __DIR__ . '/../componentes/password_utils.php';

  $usuario  = trim($_POST["usuario"] ?? "");
  $password = $_POST["password"] ?? "";

  $sentencia = $conexion->prepare("SELECT * FROM tbl_usuarios WHERE usuario = :usuario");
  $sentencia->bindParam(":usuario", $usuario);
  $sentencia->execute();
  $usuarioEncontrado = $sentencia->fetch(PDO::FETCH_ASSOC);

  if ($usuarioEncontrado) {
     $hashAlmacenado = $usuarioEncontrado["password"] ?? '';

    if (passwordCoincideConHash($password, $hashAlmacenado)) {
      if (passwordDebeRehash($hashAlmacenado) && isset($usuarioEncontrado['ID'])) {
        $nuevoHash = generarHashPassword($password);

        try {
          $actualizar = $conexion->prepare("UPDATE tbl_usuarios SET password = :password WHERE ID = :id");
          $actualizar->bindParam(':password', $nuevoHash);
          $actualizar->bindParam(':id', $usuarioEncontrado['ID']);
          $actualizar->execute();
          $hashAlmacenado = $nuevoHash;
        } catch (Throwable $error) {
          error_log('No se pudo actualizar el hash del usuario ' . $usuarioEncontrado['ID'] . ': ' . $error->getMessage());
        }
      }

      session_regenerate_id(true);
      $parametrosCookie = session_get_cookie_params();
      $esConexionSegura = piccolo_detect_scheme() === 'https';
      setcookie(session_name(), session_id(), [
        'expires' => 0,
        'path' => $parametrosCookie['path'],
        'domain' => $parametrosCookie['domain'],
        'secure' => $esConexionSegura,
        'httponly' => true,
        'samesite' => 'Strict'
      ]);
    
      $_SESSION["admin_usuario"] = $usuarioEncontrado["usuario"];
      $_SESSION["admin_logueado"] = true;
      $_SESSION["rol"] = $usuarioEncontrado["rol"];
      header("Location:index.php");
      exit();
    }
  }

  $mensaje = "Usuario o contraseña incorrectos...";
}
?>


<!doctype html>
<html lang="es">

<head>
  <title>Login Administrador</title>
  <link rel="icon" type="image/png" href="../public/img/favicon.png" />
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
