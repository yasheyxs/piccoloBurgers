<?php
require_once __DIR__ . '/../../bd.php';

verificarRol('admin');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$mensaje = $_SESSION['mensaje_premios'] ?? '';
$tipoMensaje = $_SESSION['tipo_mensaje_premios'] ?? '';
unset($_SESSION['mensaje_premios'], $_SESSION['tipo_mensaje_premios']);

if (isset($_GET['txtID'])) {
    $premioId = $_GET['txtID'];

    if (!is_numeric($premioId) || (int) $premioId <= 0) {
        $mensaje = 'El identificador del premio no es válido.';
        $tipoMensaje = 'danger';
    } else {
        try {
            $conexion->beginTransaction();

            $verificar = $conexion->prepare('SELECT COUNT(*) FROM premios WHERE id = :id');
            $verificar->bindValue(':id', (int) $premioId, PDO::PARAM_INT);
            $verificar->execute();

            if ((int) $verificar->fetchColumn() === 0) {
                $mensaje = 'El premio seleccionado no existe o ya fue eliminado.';
                $tipoMensaje = 'warning';
                $conexion->rollBack();
            } else {
                $eliminar = $conexion->prepare('DELETE FROM premios WHERE id = :id');
                $eliminar->bindValue(':id', (int) $premioId, PDO::PARAM_INT);
                $eliminar->execute();

                $conexion->commit();

                $mensaje = 'El premio se eliminó correctamente.';
                $tipoMensaje = 'success';
            }
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log('No se pudo eliminar el premio: ' . $error->getMessage());
            $mensaje = 'Ocurrió un error al intentar eliminar el premio. Probá de nuevo en unos minutos.';
            $tipoMensaje = 'danger';
        }
    }
}

try {
    $consulta = $conexion->prepare('SELECT id, nombre, descripcion, costo_puntos, created_at FROM premios ORDER BY created_at DESC, id DESC');
    $consulta->execute();
    $premios = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
    error_log('No se pudo obtener el listado de premios: ' . $error->getMessage());
    $premios = [];
    $mensaje = $mensaje ?: 'No pudimos cargar los premios. Volvé a intentarlo en unos instantes.';
    $tipoMensaje = $tipoMensaje ?: 'danger';
}

$adminPageIdentifier = 'premios-listado';
include __DIR__ . '/../../templates/header.php';
?>

<div class="py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2">Premios disponibles</h1>
            <p class="text-muted mb-0">Gestioná los premios que tus clientes pueden canjear con sus puntos.</p>
        </div>
        <div>
            <a class="btn btn-primary" href="crear.php">
                <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>
                Nuevo premio
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= htmlspecialchars($tipoMensaje ?: 'info', ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaPremios" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col" class="w-50">Descripción</th>
                            <th scope="col" class="text-center">Costo en puntos</th>
                            <th scope="col" class="text-nowrap">Creado</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($premios)): ?>
                            <?php foreach ($premios as $premio): ?>
                                <tr>
                                    <td><?= htmlspecialchars($premio['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php
                                        $descripcion = trim((string) ($premio['descripcion'] ?? ''));
                                        if ($descripcion === '') {
                                            echo '<span class="text-muted">Sin descripción</span>';
                                        } else {
                                            $descripcionRecortada = mb_strimwidth($descripcion, 0, 140, '…', 'UTF-8');
                                            echo nl2br(htmlspecialchars($descripcionRecortada, ENT_QUOTES, 'UTF-8'));
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center fw-semibold">
                                        <?= number_format((int) ($premio['costo_puntos'] ?? 0), 0, ',', '.'); ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php
                                        if (!empty($premio['created_at'])) {
                                            try {
                                                $fecha = new DateTimeImmutable($premio['created_at']);
                                                echo '<time datetime="' . htmlspecialchars($fecha->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($fecha->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8') . '</time>';
                                            } catch (Exception $e) {
                                                echo htmlspecialchars($premio['created_at'], ENT_QUOTES, 'UTF-8');
                                            }
                                        } else {
                                            echo '<span class="text-muted">—</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <a class="btn btn-sm btn-outline-primary" href="editar.php?id=<?= urlencode((string) ($premio['id'] ?? '')); ?>">
                                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                                <span class="visually-hidden">Editar</span>
                                            </a>
                                            <a class="btn btn-sm btn-outline-danger" href="index.php?txtID=<?= urlencode((string) ($premio['id'] ?? '')); ?>" onclick="return confirm('¿Seguro que querés eliminar este premio? Esta acción no se puede deshacer.');">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                <span class="visually-hidden">Eliminar</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Todavía no cargaste premios.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initDataTable('#tablaPremios', {
            order: [
                [3, 'desc']
            ]
        });
    });
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>