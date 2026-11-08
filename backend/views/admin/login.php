<?php
/**
 * Ingreso al panel.
 *
 * @var string|null $mensaje  motivo del rechazo (usuario o clave incorrectos, bloqueo por intentos…)
 * @var string $usuario
 * @var array{tipo: string, mensaje: string}|null $aviso  por ejemplo, "tu sesión se cerró"
 * @var string $csrf
 * @var string $url_publica
 */
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingreso al panel · Piccolo Burgers</title>
  <link rel="icon" href="<?= piccolo_e($url_publica) ?>img/favicon.svg" type="image/svg+xml">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container">
    <div class="row justify-content-center mt-5">
      <div class="col-12 col-sm-8 col-md-6 col-lg-4">
        <?php include __DIR__ . '/_aviso.php'; ?>
        <?php if ($mensaje): ?>
          <div class="alert alert-danger text-center" role="alert"><?= piccolo_e($mensaje) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
          <div class="card-header text-center fw-bold">Ingreso al panel</div>
          <div class="card-body p-4">
            <form action="login.php" method="post">
              <input type="hidden" name="csrf_token" value="<?= piccolo_e($csrf) ?>">
              <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" name="usuario" id="usuario" value="<?= piccolo_e($usuario) ?>" required autocomplete="username" autofocus>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="password" id="password" required autocomplete="current-password">
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">Entrar</button>
              </div>
            </form>
            <div class="mt-3 text-center">
              <a href="password/recuperar_password_usuario.php">¿Olvidaste tu contraseña?</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
