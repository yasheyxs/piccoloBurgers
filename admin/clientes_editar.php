<?php
include("../admin/bd.php");
require_once __DIR__ . '/helpers/telefonos.php';
require_once __DIR__ . '/../componentes/validar_telefono.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$clienteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($clienteId <= 0) {
    header('Location: clientes.php');
    exit();
}

try {
    $stmt = $conexion->prepare('SELECT * FROM tbl_clientes WHERE ID = :id LIMIT 1');
    $stmt->bindParam(':id', $clienteId, PDO::PARAM_INT);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $_SESSION['error_clientes'] = 'No encontramos el cliente que querés editar.';
        header('Location: clientes.php');
        exit();
    }
} catch (Throwable $error) {
    error_log('No se pudo obtener al cliente: ' . $error->getMessage());
    $_SESSION['error_clientes'] = 'Ocurrió un error al cargar los datos del cliente.';
    header('Location: clientes.php');
    exit();
}

$errores = [];

$catalogoTelefonos = obtenerCatalogoTelefonos();
$telefonosFormulario = dividirTelefonoEnCodigoYNumero($cliente['telefono'] ?? '');
$codigoPais = $telefonosFormulario['codigo'];
$numeroTelefono = $telefonosFormulario['numero'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenSesion = $_SESSION['csrf_token'] ?? '';
    $tokenRecibido = $_POST['csrf_token'] ?? '';

    if (!$tokenRecibido || !$tokenSesion || !hash_equals($tokenSesion, $tokenRecibido)) {
        http_response_code(403);
        exit('Token CSRF inválido.');
    }

    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $puntosInput = trim((string) ($_POST['puntos'] ?? ''));
    $codigoPaisInput = normalizarCodigoTelefono((string) ($_POST['codigo_pais'] ?? $codigoPais));

    if (isset($catalogoTelefonos[$codigoPaisInput])) {
        $codigoPais = $codigoPaisInput;
    } else {
        $errores[] = 'Seleccioná un código de país válido.';
    }

    $telefonoSinFiltrar = trim((string) ($_POST['telefono'] ?? ''));
    $numeroTelefono = preg_replace('/[^\d]/', '', $telefonoSinFiltrar);

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
        $stmtVerificarEmail = $conexion->prepare('SELECT COUNT(*) FROM tbl_clientes WHERE email = :email AND ID <> :id');
        $stmtVerificarEmail->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtVerificarEmail->bindParam(':id', $clienteId, PDO::PARAM_INT);
        $stmtVerificarEmail->execute();
        if ((int) $stmtVerificarEmail->fetchColumn() > 0) {
            $errores[] = 'Ya existe un cliente registrado con ese correo electrónico.';
        }
    }

    if ($telefonoNormalizado !== false) {
        $stmtVerificarTelefono = $conexion->prepare('SELECT COUNT(*) FROM tbl_clientes WHERE telefono = :telefono AND ID <> :id');
        $stmtVerificarTelefono->bindParam(':telefono', $telefonoNormalizado, PDO::PARAM_STR);
        $stmtVerificarTelefono->bindParam(':id', $clienteId, PDO::PARAM_INT);
        $stmtVerificarTelefono->execute();
        if ((int) $stmtVerificarTelefono->fetchColumn() > 0) {
            $errores[] = 'Ya existe un cliente registrado con ese número de teléfono.';
        }
    }

    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $stmtActualizar = $conexion->prepare('UPDATE tbl_clientes SET nombre = :nombre, telefono = :telefono, email = :email, puntos = :puntos WHERE ID = :id');
            $stmtActualizar->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmtActualizar->bindParam(':telefono', $telefonoNormalizado, PDO::PARAM_STR);
            $stmtActualizar->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtActualizar->bindParam(':puntos', $puntosValidados, PDO::PARAM_INT);
            $stmtActualizar->bindParam(':id', $clienteId, PDO::PARAM_INT);
            $stmtActualizar->execute();

            $puntosAnteriores = (int) ($cliente['puntos'] ?? 0);
            $diferenciaPuntos = $puntosValidados - $puntosAnteriores;

            if ($diferenciaPuntos !== 0) {
                $descripcion = $diferenciaPuntos > 0 ? 'Ajuste manual: suma de puntos desde el panel' : 'Ajuste manual: descuento de puntos desde el panel';
                $stmtMovimiento = $conexion->prepare('INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (:cliente_id, :tipo, :descripcion, :puntos, :saldo)');
                $tipoMovimiento = 'ajuste';
                $stmtMovimiento->bindParam(':cliente_id', $clienteId, PDO::PARAM_INT);
                $stmtMovimiento->bindParam(':tipo', $tipoMovimiento, PDO::PARAM_STR);
                $stmtMovimiento->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                $stmtMovimiento->bindParam(':puntos', $diferenciaPuntos, PDO::PARAM_INT);
                $stmtMovimiento->bindParam(':saldo', $puntosValidados, PDO::PARAM_INT);
                $stmtMovimiento->execute();
            }

            $conexion->commit();
            $_SESSION['mensaje_clientes'] = 'Los datos del cliente se actualizaron correctamente.';
            header('Location: clientes.php');
            exit();
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log('No se pudo actualizar el cliente: ' . $error->getMessage());
            $errores[] = 'Ocurrió un error al guardar los cambios. Intentá nuevamente.';
        }
    }

    // Actualizar los valores en memoria para repoblar el formulario
    $cliente['nombre'] = $nombre;
    if ($telefonoNormalizado !== false) {
        $cliente['telefono'] = $telefonoNormalizado;
    }
    $cliente['email'] = $email;
    $cliente['puntos'] = $puntosValidados !== false ? $puntosValidados : $cliente['puntos'];
}

$telefonosFormulario = dividirTelefonoEnCodigoYNumero($cliente['telefono'] ?? '');
if (empty($errores)) {
    $codigoPais = $telefonosFormulario['codigo'];
    $numeroTelefono = $telefonosFormulario['numero'];
}

include("../admin/templates/header.php");
?>

<br>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0">Editar cliente</h5>
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
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
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
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="puntos" class="form-label">Puntos</label>
                <input type="number" min="0" class="form-control" id="puntos" name="puntos" value="<?= htmlspecialchars((string) ($cliente['puntos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="clientes.php" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    Guardar cambios
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