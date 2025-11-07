<?php
include("../admin/bd.php");
require_once __DIR__ . '/helpers/telefonos.php';
require_once __DIR__ . '/../componentes/validar_telefono.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errores = [];

$nombre = '';
$email = '';
$puntos = 0;

$catalogoTelefonos = obtenerCatalogoTelefonos();
$codigoPais = array_key_first($catalogoTelefonos) ?: '54';
$numeroTelefono = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenSesion = $_SESSION['csrf_token'] ?? '';
    $tokenRecibido = $_POST['csrf_token'] ?? '';

    if (!$tokenRecibido || !$tokenSesion || !hash_equals($tokenSesion, $tokenRecibido)) {
        http_response_code(403);
        exit('Token CSRF inválido.');
    }

    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $codigoPaisInput = normalizarCodigoTelefono((string) ($_POST['codigo_pais'] ?? $codigoPais));
    if (isset($catalogoTelefonos[$codigoPaisInput])) {
        $codigoPais = $codigoPaisInput;
    } else {
        $errores[] = 'Seleccioná un código de país válido.';
    }

    $telefonoSinFiltrar = trim((string) ($_POST['telefono'] ?? ''));
    $numeroTelefono = preg_replace('/[^\d]/', '', $telefonoSinFiltrar);
    $puntosInput = trim((string) ($_POST['puntos'] ?? '0'));

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresá un correo electrónico válido.';
    }

    $telefonoNormalizado = validarTelefono($codigoPais, $telefonoSinFiltrar);
    if ($telefonoNormalizado === false) {
        $errores[] = 'Ingresá un número de teléfono válido.';
    }

    $puntosValidados = filter_var($puntosInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($puntosValidados === false) {
        $errores[] = 'Ingresá un valor de puntos válido (0 o más).';
    }

    if ($email !== '') {
        $stmtVerificarEmail = $conexion->prepare('SELECT COUNT(*) FROM tbl_clientes WHERE email = :email');
        $stmtVerificarEmail->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtVerificarEmail->execute();
        if ((int) $stmtVerificarEmail->fetchColumn() > 0) {
            $errores[] = 'Ya existe un cliente registrado con ese correo electrónico.';
        }
    }

    if ($telefonoNormalizado !== false) {
        $stmtVerificarTelefono = $conexion->prepare('SELECT COUNT(*) FROM tbl_clientes WHERE telefono = :telefono');
        $stmtVerificarTelefono->bindParam(':telefono', $telefonoNormalizado, PDO::PARAM_STR);
        $stmtVerificarTelefono->execute();
        if ((int) $stmtVerificarTelefono->fetchColumn() > 0) {
            $errores[] = 'Ya existe un cliente registrado con ese número de teléfono.';
        }
    }

    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $passwordGenerica = password_hash('12345', PASSWORD_BCRYPT);
            $fechaRegistro = date('Y-m-d H:i:s');

            $stmtInsertar = $conexion->prepare('INSERT INTO tbl_clientes (nombre, telefono, email, password, fecha_registro, puntos) VALUES (:nombre, :telefono, :email, :password, :fecha_registro, :puntos)');
            $stmtInsertar->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmtInsertar->bindParam(':telefono', $telefonoNormalizado, PDO::PARAM_STR);
            $stmtInsertar->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtInsertar->bindParam(':password', $passwordGenerica, PDO::PARAM_STR);
            $stmtInsertar->bindParam(':fecha_registro', $fechaRegistro, PDO::PARAM_STR);
            $stmtInsertar->bindParam(':puntos', $puntosValidados, PDO::PARAM_INT);
            $stmtInsertar->execute();

            $nuevoClienteId = (int) $conexion->lastInsertId();

            if ($puntosValidados > 0) {
                $stmtMovimiento = $conexion->prepare('INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (:cliente_id, :tipo, :descripcion, :puntos, :saldo)');
                $tipoMovimiento = 'ajuste';
                $descripcion = 'Asignación inicial de puntos al crear cliente';
                $stmtMovimiento->bindParam(':cliente_id', $nuevoClienteId, PDO::PARAM_INT);
                $stmtMovimiento->bindParam(':tipo', $tipoMovimiento, PDO::PARAM_STR);
                $stmtMovimiento->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                $stmtMovimiento->bindParam(':puntos', $puntosValidados, PDO::PARAM_INT);
                $stmtMovimiento->bindParam(':saldo', $puntosValidados, PDO::PARAM_INT);
                $stmtMovimiento->execute();
            }

            $conexion->commit();

            $_SESSION['mensaje_clientes'] = 'El cliente se creó correctamente. La contraseña asignada es 12345.';
            header('Location: clientes.php');
            exit();
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            error_log('No se pudo crear el cliente: ' . $error->getMessage());
            $errores[] = 'Ocurrió un error al crear el cliente. Intentá nuevamente.';
        }
    }

    $puntos = $puntosValidados !== false ? $puntosValidados : $puntos;
}

include("../admin/templates/header.php");
?>

<br>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0">Crear nuevo cliente</h5>
        <a href="clientes.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>
            Volver al listado
        </a>
    </div>
    <div class="card-body">
        <?php if ($errores) { ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errores as $error) { ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <div class="col-md-6">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="telefono" class="form-label">Teléfono</label>
                <div class="input-group">
                    <label class="input-group-text" for="codigo_pais">Cód.</label>
                    <select class="form-select" id="codigo_pais" name="codigo_pais" required style="max-width: 100px;">

                        <?php foreach ($catalogoTelefonos as $codigo => $datos) { ?>
                            <option value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>" <?= $codigo === $codigoPais ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($datos['bandera'] . ' +' . $codigo . ' ' . $datos['pais'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($numeroTelefono, ENT_QUOTES, 'UTF-8'); ?>" required inputmode="numeric" autocomplete="tel" placeholder="Ej: 3511234567">
                </div>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="puntos" class="form-label">Puntos iniciales</label>
                <input type="number" min="0" class="form-control" id="puntos" name="puntos" value="<?= htmlspecialchars((string) $puntos, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-12">
                <div class="alert alert-info alert-soft-info" role="alert">
                    El cliente se creará con la contraseña genérica <span class="fw-semibold">12345</span>. Recordale que la cambie cuanto antes.
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="clientes.php" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-user-plus me-1"></i>
                    Crear cliente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const codigoSelect = document.getElementById('codigo_pais');
        const telefonoInput = document.getElementById('telefono');
        const longitudes = <?= json_encode(array_map(fn($datos) => $datos['longitudes'], $catalogoTelefonos), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        if (!codigoSelect || !telefonoInput) {
            return;
        }

        function aplicarLongitudMaxima() {
            const codigo = codigoSelect.value;
            const longitudesPermitidas = longitudes[codigo];

            if (Array.isArray(longitudesPermitidas) && longitudesPermitidas.length > 0) {
                const maximo = Math.max.apply(null, longitudesPermitidas);
                telefonoInput.maxLength = Number.isFinite(maximo) ? maximo : 15;
            } else {
                telefonoInput.removeAttribute('maxLength');
            }
        }

        codigoSelect.addEventListener('change', aplicarLongitudMaxima);
        telefonoInput.addEventListener('input', () => {
            telefonoInput.value = telefonoInput.value.replace(/[^\d]/g, '');
        });

        aplicarLongitudMaxima();
    })();
</script>

<?php include("../admin/templates/footer.php"); ?>