<?php
require_once __DIR__ . '/../../bd.php';

verificarRol('admin');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errores = [];
$nombre = '';
$descripcion = '';
$costoPuntos = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
    $costoPuntos = trim((string) ($_POST['costo_puntos'] ?? ''));

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
            $insertar = $conexion->prepare('INSERT INTO premios (nombre, descripcion, costo_puntos) VALUES (:nombre, :descripcion, :costo)');
            $insertar->bindValue(':nombre', $nombre, PDO::PARAM_STR);
            if ($descripcion !== '') {
                $insertar->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
            } else {
                $insertar->bindValue(':descripcion', null, PDO::PARAM_NULL);
            }
            $insertar->bindValue(':costo', (int) $costoValidado, PDO::PARAM_INT);
            $insertar->execute();

            $_SESSION['mensaje_premios'] = 'El premio se creó correctamente.';
            $_SESSION['tipo_mensaje_premios'] = 'success';

            header('Location: index.php');
            exit;
        } catch (Throwable $error) {
            error_log('No se pudo crear el premio: ' . $error->getMessage());
            $errores[] = 'Ocurrió un error al guardar el premio. Intentá nuevamente en unos minutos.';
        }
    }
}

$adminPageIdentifier = 'premios-crear';
include __DIR__ . '/../../templates/header.php';
?>

<div class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-2">Nuevo premio</h1>
        <p class="text-muted mb-0">Definí los detalles del premio que estará disponible para canje en tu programa de puntos.</p>
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
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre del premio<span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Ej.: Combo Deluxe">
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea
                        class="form-control"
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                        maxlength="1000"
                        placeholder="Añadí detalles del premio para tus colaboradores y clientes"><?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?></textarea>
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
                    <div class="form-text">Ingresá la cantidad de puntos que requiere el canje.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>
                        Guardar premio
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>