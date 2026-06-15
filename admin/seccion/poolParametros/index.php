<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/pool_schema.php';

verificarRol('admin');
asegurarTablaPoolTurnos($conexion);

$errores = [];
$mensajeExito = '';

try {
    $configuracionActual = poolObtenerConfiguracion($conexion);
} catch (Throwable $error) {
    error_log('No se pudo obtener la configuración de POOLS: ' . $error->getMessage());
    $errores[] = 'No pudimos cargar los parámetros actuales de POOLS. Recarga la página.';
    $configuracionActual = [
        'max_fichas_por_registro' => 3,
        'valor_ficha' => 1000,
        'actualizado_en' => null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenSesion = $_SESSION['csrf_token'] ?? '';
    $tokenRecibido = $_POST['csrf_token'] ?? '';

    if (!is_string($tokenRecibido) || !hash_equals($tokenSesion, $tokenRecibido)) {
        $errores[] = 'El token de seguridad es inválido. Recarga la página e intenta nuevamente.';
    } else {
        $maxFichasInput = trim((string) ($_POST['max_fichas_por_registro'] ?? ''));
        $valorFichaInput = trim((string) ($_POST['valor_ficha'] ?? ''));

        $maxFichas = filter_var($maxFichasInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        if ($maxFichas === false) {
            $errores[] = 'Ingresa una cantidad máxima de fichas por registro entre 1 y 99.';
        }

        $valorFichaNormalizado = str_replace(',', '.', $valorFichaInput);
        $valorFicha = filter_var($valorFichaNormalizado, FILTER_VALIDATE_FLOAT);
        if ($valorFicha === false || $valorFicha <= 0) {
            $errores[] = 'Ingresa un valor monetario por ficha mayor a 0.';
        }

        if (empty($errores)) {
            try {
                poolActualizarConfiguracion($conexion, (int) $maxFichas, round((float) $valorFicha, 2));
                $configuracionActual = poolObtenerConfiguracion($conexion);
                $mensajeExito = 'Los parámetros de POOLS se actualizaron correctamente.';
            } catch (Throwable $error) {
                error_log('No se pudo actualizar la configuración de POOLS: ' . $error->getMessage());
                $errores[] = 'Ocurrió un error al guardar los cambios. Probá nuevamente en unos minutos.';
            }
        }
    }
}

$maxFichasActual = (int) ($configuracionActual['max_fichas_por_registro'] ?? 3);
$valorFichaActual = (float) ($configuracionActual['valor_ficha'] ?? 1000);
$valorFichaFormulario = rtrim(rtrim(number_format($valorFichaActual, 2, '.', ''), '0'), '.');
if ($valorFichaFormulario === '') {
    $valorFichaFormulario = '0';
}

$ultimaActualizacionTexto = null;
$ultimaActualizacionIso = null;
$ultimaActualizacionTitulo = null;

if (!empty($configuracionActual['actualizado_en'])) {
    $fecha = poolFechaDbAFechaApp($configuracionActual['actualizado_en']);
    if ($fecha) {
        $ultimaActualizacionTexto = $fecha->format('d/m/Y H:i');
        $ultimaActualizacionIso = $fecha->format(DateTimeInterface::ATOM);
        $ultimaActualizacionTitulo = 'Actualizado el ' . $fecha->format('d/m/Y H:i') . ' hs';
    } else {
        $ultimaActualizacionTexto = $configuracionActual['actualizado_en'];
    }
}

$adminPageIdentifier = 'pool-parametros';
include __DIR__ . '/../../templates/header.php';
?>

<div class="py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2 d-flex align-items-center gap-2">
                Parámetros de POOLS
            </h1>
            <p class="text-muted mb-0">
                Ajustá cómo se registran las fichas y el valor económico que alimenta las estadísticas.
            </p>
        </div>
        <div class="d-flex flex-column align-items-center align-items-lg-end gap-2">
            <a class="btn btn-outline-primary" href="../pool/"
                title="Volver a POOLS" aria-label="Volver a POOLS">
                <i class="fa-solid fa-circle-dot" aria-hidden="true"></i>
            </a>
            <div class="last-update-badge shadow-sm px-3 py-2">
                <span class="last-update-icon" aria-hidden="true">
                    <i class="fa-solid fa-clock text-warning"></i>
                </span>
                <div class="d-flex flex-column align-items-center text-center">
                    <span class="last-update-label">Última actualización</span>
                    <?php if ($ultimaActualizacionTexto): ?>
                        <time
                            class="last-update-value fw-semibold"
                            datetime="<?= htmlspecialchars($ultimaActualizacionIso ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-last-update="<?= htmlspecialchars($ultimaActualizacionIso ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            title="<?= htmlspecialchars($ultimaActualizacionTitulo ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($ultimaActualizacionTexto, ENT_QUOTES, 'UTF-8') ?>
                        </time>
                        <span class="last-update-relative" data-last-update-relative></span>
                    <?php else: ?>
                        <span class="last-update-value fw-semibold">Sin registros</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <strong>Ups.</strong> Revisa los siguientes puntos:
            <ul class="mb-0 mt-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if ($mensajeExito): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>
            <?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
            <h2 class="h5 mb-1 d-flex align-items-center gap-2">
                Parámetros generales
            </h2>
            <br>
            <p class="text-muted mb-0">
                Estos valores se aplican a los nuevos registros de fichas y a los cálculos monetarios.
            </p>
        </div>
        <div class="card-body">
            <form method="post" class="row g-4" data-pool-config-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="col-12 col-lg-6">
                    <label for="max_fichas_por_registro" class="form-label fw-semibold">
                        Cantidad máxima de fichas por registro
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-warning bg-opacity-10 border-0">
                            <i class="fa-solid fa-layer-group text-warning"></i>
                        </span>
                        <input
                            type="number"
                            class="form-control"
                            id="max_fichas_por_registro"
                            name="max_fichas_por_registro"
                            min="1"
                            max="99"
                            step="1"
                            value="<?= htmlspecialchars((string) $maxFichasActual, ENT_QUOTES, 'UTF-8') ?>"
                            data-default-value="<?= htmlspecialchars((string) $maxFichasActual, ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>
                    <div class="form-text">
                        Si se venden más fichas que este límite, el sistema divide automáticamente el registro.
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label for="valor_ficha" class="form-label fw-semibold">
                        Valor monetario de cada ficha (ARS)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-warning bg-opacity-10 border-0">
                            <i class="fa-solid fa-money-bill-wave text-warning"></i>
                        </span>
                        <input
                            type="number"
                            class="form-control"
                            id="valor_ficha"
                            name="valor_ficha"
                            min="0.01"
                            step="0.01"
                            value="<?= htmlspecialchars($valorFichaFormulario, ENT_QUOTES, 'UTF-8') ?>"
                            data-default-value="<?= htmlspecialchars($valorFichaFormulario, ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>
                    <div class="form-text">
                        Este importe se guarda en cada venta de fichas para conservar estadísticas históricas.
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="restablecerParametrosPool">
                        <i class="fa-solid fa-rotate-left me-2"></i>Ver datos actuales
                    </button>
                    <button type="submit" class="btn btn-warning text-dark shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formulario = document.querySelector('[data-pool-config-form]');
            const restablecerBtn = document.getElementById('restablecerParametrosPool');

            if (formulario && restablecerBtn) {
                restablecerBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    formulario.reset();
                    formulario.querySelectorAll('[data-default-value]').forEach(function(input) {
                        const defaultValue = input.getAttribute('data-default-value');
                        if (defaultValue !== null) {
                            input.value = defaultValue;
                        }
                    });
                });
            }

            const lastUpdateElement = document.querySelector('[data-last-update]');
            const relativeTarget = document.querySelector('[data-last-update-relative]');

            function actualizarTiempoRelativo() {
                if (!lastUpdateElement || !relativeTarget) return;
                const iso = lastUpdateElement.getAttribute('data-last-update');
                if (!iso) return;
                const fecha = new Date(iso);
                if (Number.isNaN(fecha.getTime())) return;
                const diferenciaMs = Date.now() - fecha.getTime();
                const minutos = Math.max(0, Math.floor(diferenciaMs / 60000));
                if (minutos < 1) {
                    relativeTarget.textContent = 'Hace instantes';
                    return;
                }
                if (minutos < 60) {
                    relativeTarget.textContent = `Hace ${minutos} minuto${minutos === 1 ? '' : 's'}`;
                    return;
                }
                const horas = Math.floor(minutos / 60);
                relativeTarget.textContent = `Hace ${horas} hora${horas === 1 ? '' : 's'}`;
            }

            actualizarTiempoRelativo();
            setInterval(actualizarTiempoRelativo, 60000);
        });
    </script>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
