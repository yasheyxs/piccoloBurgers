<?php
require_once __DIR__ . '/../../bd.php';

verificarRol('admin');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errores = [];
$premioId = $_GET['id'] ?? null;
$nombre = '';
$descripcion = '';
$costoPuntos = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $premioId = $_POST['id'] ?? null;
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
    $costoPuntos = trim((string) ($_POST['costo_puntos'] ?? ''));

    if (!is_numeric($premioId) || (int) $premioId <= 0) {
        $errores[] = 'El identificador del premio es inválido.';
    }

    if ($nombre === '') {
        $errores[] = 'El nombre del premio es obligatorio.';
    } elseif (mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre no puede superar los 100 caracteres.';
    }

    if ($descripcion !== '' && mb_strlen($descripcion) > 1000) {
        $errores[] = 'La descripción no puede superar los 1000 caracteres.';
    }

    $costoValidado = filter_var($costoPuntos, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($costoValidado === false) {
        $errores[] = 'Ingresá un costo en puntos válido (mayor o igual a 1).';
    }

    if (empty($errores)) {
        try {
            $actualizar = $conexion->prepare('UPDATE premios SET nombre = :nombre, descripcion = :descripcion, costo_puntos = :costo WHERE id = :id');
            $actualizar->bindValue(':nombre', $nombre, PDO::PARAM_STR);
            if ($descripcion !== '') {
                $actualizar->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
            } else {
                $actualizar->bindValue(':descripcion', null, PDO::PARAM_NULL);
            }
            $actualizar->bindValue(':costo', (int) $costoValidado, PDO::PARAM_INT);
            $actualizar->bindValue(':id', (int) $premioId, PDO::PARAM_INT);
            $actualizar->execute();

            $_SESSION['mensaje_premios'] = 'Los cambios se guardaron correctamente.';
            $_SESSION['tipo_mensaje_premios'] = 'success';

            header('Location: index.php');
            exit;
        } catch (Throwable $error) {
            error_log('No se pudo actualizar el premio: ' . $error->getMessage());
            $errores[] = 'Ocurrió un error al guardar los cambios. Intentá nuevamente en unos minutos.';
        }
    }
} else {
    if (!is_numeric($premioId) || (int) $premioId <= 0) {
        $_SESSION['mensaje_premios'] = 'No encontramos el premio que querés editar.';
        $_SESSION['tipo_mensaje_premios'] = 'warning';
        header('Location: index.php');
        exit;
    }

    try {
        $buscar = $conexion->prepare('SELECT id, nombre, descripcion, costo_puntos FROM premios WHERE id = :id');
        $buscar->bindValue(':id', (int) $premioId, PDO::PARAM_INT);
        $buscar->execute();
        $premio = $buscar->fetch(PDO::FETCH_ASSOC);

        if (!$premio) {
            $_SESSION['mensaje_premios'] = 'No encontramos el premio que querés editar.';
            $_SESSION['tipo_mensaje_premios'] = 'warning';
            header('Location: index.php');
            exit;
        }

        $nombre = (string) ($premio['nombre'] ?? '');
        $descripcion = (string) ($premio['descripcion'] ?? '');
        $costoPuntos = (string) ($premio['costo_puntos'] ?? '');
    } catch (Throwable $error) {
        error_log('No se pudo cargar el premio: ' . $error->getMessage());
        $_SESSION['mensaje_premios'] = 'No pudimos cargar el premio seleccionado.';
        $_SESSION['tipo_mensaje_premios'] = 'danger';
        header('Location: index.php');
        exit;
    }
}

$adminPageIdentifier = 'premios-editar';
include __DIR__ . '/../../templates/header.php';
?>

<div class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-2">Editar premio</h1>
        <p class="text-muted mb-0">Actualizá la información del premio seleccionado sin perder la coherencia del sistema de puntos.</p>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="" method="post" novalidate>
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $premioId, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre del premio<span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea
                        class="form-control"
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                        maxlength="1000"><?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <div class="form-text">Opcional. Hasta 1000 caracteres.</div>
                </div>

                <div class="mb-4">
                    <label for="costo_puntos" class="form-label">Costo en puntos<span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
                        <input
                            type="number"
                            class="form-control"
                            id="costo_puntos"
                            name="costo_puntos"
                            min="1"
                            step="1"
                            required
                            value="<?= htmlspecialchars($costoPuntos, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>
                        Guardar cambios
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>