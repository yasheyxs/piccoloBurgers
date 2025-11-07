<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../includes/puntos_config.php';

verificarRol('admin');

$errores = [];
$mensajeExito = '';

try {
    $configuracionActual = obtenerConfiguracionPuntos($conexion);
} catch (Throwable $error) {
    error_log('No se pudo obtener la configuración de puntos: ' . $error->getMessage());
    $errores[] = 'No pudimos cargar los parámetros actuales del sistema de puntos. Por favor, recargá la página.';
    $configuracionActual = [
        'minimo_puntos' => 50,
        'valor_punto' => 20.0,
        'maximo_porcentaje' => 0.25,
        'actualizado_en' => null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenSesion = $_SESSION['csrf_token'] ?? '';
    $tokenRecibido = $_POST['csrf_token'] ?? '';

    if (!is_string($tokenRecibido) || !hash_equals($tokenSesion, $tokenRecibido)) {
        $errores[] = 'El token de seguridad es inválido. Recargá la página e intentá nuevamente.';
    } else {
        $minimoPuntosInput = trim((string) ($_POST['minimo_puntos'] ?? ''));
        $valorPuntoInput = trim((string) ($_POST['valor_punto'] ?? ''));
        $maximoPorcentajeInput = trim((string) ($_POST['maximo_porcentaje'] ?? ''));

        $minimoPuntos = filter_var(
            $minimoPuntosInput,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );
        if ($minimoPuntos === false) {
            $errores[] = 'Ingresá un mínimo de puntos válido (0 o más).';
        }

        $valorPuntoNormalizado = str_replace(',', '.', $valorPuntoInput);
        $valorPunto = filter_var($valorPuntoNormalizado, FILTER_VALIDATE_FLOAT);
        if ($valorPunto === false || $valorPunto <= 0) {
            $errores[] = 'Ingresá un valor por punto mayor a 0.';
        }

        $maximoPorcentajeNormalizado = str_replace(',', '.', $maximoPorcentajeInput);
        $maximoPorcentaje = filter_var($maximoPorcentajeNormalizado, FILTER_VALIDATE_FLOAT);
        if ($maximoPorcentaje === false || $maximoPorcentaje <= 0 || $maximoPorcentaje > 100) {
            $errores[] = 'El porcentaje máximo debe estar entre 0 y 100.';
        }

        if (empty($errores)) {
            try {
                $maximoPorcentajeDecimal = round($maximoPorcentaje / 100, 4);
                actualizarConfiguracionPuntos(
                    $conexion,
                    (int) $minimoPuntos,
                    round((float) $valorPunto, 2),
                    $maximoPorcentajeDecimal
                );
                $configuracionActual = obtenerConfiguracionPuntos($conexion);
                $mensajeExito = 'Los parámetros del sistema de puntos se actualizaron correctamente.';
            } catch (Throwable $error) {
                error_log('No se pudo actualizar la configuración de puntos: ' . $error->getMessage());
                $errores[] = 'Ocurrió un error al guardar los cambios. Probá nuevamente en unos minutos.';
            }
        }
    }
}

$minimoPuntosActual = (int) ($configuracionActual['minimo_puntos'] ?? 50);
$valorPuntoActual = (float) ($configuracionActual['valor_punto'] ?? 20.0);
$maximoPorcentajeActual = (float) ($configuracionActual['maximo_porcentaje'] ?? 0.25);
$maximoPorcentajeMostrar = $maximoPorcentajeActual * 100;

$valorPuntoFormulario = rtrim(rtrim(number_format($valorPuntoActual, 2, '.', ''), '0'), '.');
if ($valorPuntoFormulario === '') {
    $valorPuntoFormulario = '0';
}
$maximoPorcentajeFormulario = rtrim(rtrim(number_format($maximoPorcentajeMostrar, 2, '.', ''), '0'), '.');
if ($maximoPorcentajeFormulario === '') {
    $maximoPorcentajeFormulario = '0';
}

$ultimaActualizacionTexto = null;
$ultimaActualizacionIso = null;
$ultimaActualizacionZona = null;
$ultimaActualizacionTitulo = null;

if (!empty($configuracionActual['actualizado_en'])) {
    $dbTimezoneId = getenv('DB_TIMEZONE') ?: 'UTC';
    $appTimezoneId = getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires';

    try {
        $dbTimezone = new DateTimeZone($dbTimezoneId);
    } catch (Exception $e) {
        $dbTimezone = new DateTimeZone('UTC');
    }
    try {
        $appTimezone = new DateTimeZone($appTimezoneId);
    } catch (Exception $e) {
        $appTimezone = $dbTimezone;
    }

    try {
        $fechaOriginal = new DateTimeImmutable($configuracionActual['actualizado_en'], $dbTimezone);
        $fechaConvertida = $fechaOriginal->setTimezone($appTimezone);
        $ultimaActualizacionTexto = $fechaConvertida->format('d/m/Y H:i');
        $ultimaActualizacionIso = $fechaConvertida->format(DateTimeInterface::ATOM);
        $ultimaActualizacionZona = $fechaConvertida->format('T');
        $ultimaActualizacionTitulo = 'Actualizado el ' . $fechaConvertida->format('d/m/Y H:i') . ' hs' . ($ultimaActualizacionZona ? ' (' . $ultimaActualizacionZona . ')' : '');
    } catch (Exception $e) {
        $ultimaActualizacionTexto = $configuracionActual['actualizado_en'];
    }
}

$adminPageIdentifier = 'puntos-config';
include __DIR__ . '/../../templates/header.php';
?>

<div class="py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2 d-flex align-items-center gap-2">
                Sistema de puntos
            </h1>
            <p class="text-muted mb-0">
                Ajustá los parámetros que definen cómo tus clientes canjean y aprovechan sus puntos de fidelidad.
            </p>
        </div>
        <div class="d-flex flex-column align-items-center align-items-lg-end gap-2">
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
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <strong>Ups.</strong> Revisá los siguientes puntos:
            <ul class="mb-0 mt-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($mensajeExito): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>
            <div><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
            <h2 class="h5 mb-1 d-flex align-items-center gap-2">
                Parámetros generales
            </h2>
            <br>
            <p class="text-muted mb-0">
                Definí límites claros para ofrecer beneficios equilibrados y sostenibles.
            </p>
        </div>
        <div class="card-body">
            <form method="post" class="row g-4" data-puntos-config-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="col-12 col-lg-4">
                    <label for="minimo_puntos" class="form-label fw-semibold">
                        Mínimo de puntos para canjear
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-warning bg-opacity-10 border-0">
                            <i class="fa-solid fa-flag-checkered text-warning"></i>
                        </span>
                        <input
                            type="number"
                            class="form-control"
                            id="minimo_puntos"
                            name="minimo_puntos"
                            min="0"
                            step="1"
                            value="<?= htmlspecialchars((string) $minimoPuntosActual, ENT_QUOTES, 'UTF-8') ?>"
                            data-default-value="<?= htmlspecialchars((string) $minimoPuntosActual, ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>
                    <div class="form-text">
                        Define desde cuántos puntos se habilita el canje para el cliente.
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <label for="valor_punto" class="form-label fw-semibold">
                        Valor monetario de cada punto (ARS)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-warning bg-opacity-10 border-0">
                            <i class="fa-solid fa-money-bill-wave text-warning"></i>
                        </span>
                        <input
                            type="number"
                            class="form-control"
                            id="valor_punto"
                            name="valor_punto"
                            min="0.01"
                            step="0.01"
                            value="<?= htmlspecialchars($valorPuntoFormulario, ENT_QUOTES, 'UTF-8') ?>"
                            data-default-value="<?= htmlspecialchars($valorPuntoFormulario, ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>
                    <div class="form-text">
                        Cuánto descuenta cada punto aplicado en la compra.
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <label for="maximo_porcentaje" class="form-label fw-semibold">
                        Porcentaje máximo de canje
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-warning bg-opacity-10 border-0">
                            <i class="fa-solid fa-percent text-warning"></i>
                        </span>
                        <input
                            type="number"
                            class="form-control"
                            id="maximo_porcentaje"
                            name="maximo_porcentaje"
                            min="1"
                            max="100"
                            step="0.1"
                            value="<?= htmlspecialchars($maximoPorcentajeFormulario, ENT_QUOTES, 'UTF-8') ?>"
                            data-default-value="<?= htmlspecialchars($maximoPorcentajeFormulario, ENT_QUOTES, 'UTF-8') ?>"
                            required>

                    </div>
                    <div class="form-text">
                        Tope de la compra que puede cubrirse con puntos (por ejemplo, 25%).
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="restablecerParametros">
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
            const formulario = document.querySelector('[data-puntos-config-form]');
            const restablecerBtn = document.getElementById('restablecerParametros');

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
                    formulario.dispatchEvent(new Event('piccolo:form-restablecido'));
                });
            }

            const lastUpdateElement = document.querySelector('[data-last-update]');
            const relativeTarget = document.querySelector('[data-last-update-relative]');

            function textoRelativoParaDiferencia(diffMs) {
                if (!Number.isFinite(diffMs)) {
                    return '';
                }

                const esFuturo = diffMs < 0;
                const absMs = Math.abs(diffMs);
                const segundos = Math.floor(absMs / 1000);
                if (segundos < 60) {
                    return esFuturo ? 'En instantes' : 'Hace instantes';
                }

                const minutos = Math.floor(segundos / 60);
                if (minutos < 60) {
                    return esFuturo ?
                        `En ${minutos} minuto${minutos === 1 ? '' : 's'}` :
                        `Hace ${minutos} minuto${minutos === 1 ? '' : 's'}`;
                }

                const horas = Math.floor(minutos / 60);
                if (horas < 24) {
                    return esFuturo ?
                        `En ${horas} hora${horas === 1 ? '' : 's'}` :
                        `Hace ${horas} hora${horas === 1 ? '' : 's'}`;
                }

                const dias = Math.floor(horas / 24);
                return esFuturo ?
                    `En ${dias} día${dias === 1 ? '' : 's'}` :
                    `Hace ${dias} día${dias === 1 ? '' : 's'}`;
            }

            function actualizarTiempoRelativo() {
                if (!lastUpdateElement || !relativeTarget) {
                    return;
                }

                const iso = lastUpdateElement.getAttribute('data-last-update');
                if (!iso) {
                    relativeTarget.textContent = '';
                    return;
                }

                const fecha = new Date(iso);
                if (Number.isNaN(fecha.getTime())) {
                    relativeTarget.textContent = '';
                    return;
                }

                try {
                    const formatter = new Intl.DateTimeFormat('es-AR', {
                        dateStyle: 'full',
                        timeStyle: 'short'
                    });
                    lastUpdateElement.setAttribute('title', formatter.format(fecha));
                } catch (error) {
                    // Si Intl no está disponible, dejamos el título provisto por el servidor.
                }

                const ahora = new Date();
                const diffMs = ahora.getTime() - fecha.getTime();
                relativeTarget.textContent = textoRelativoParaDiferencia(diffMs);
            }

            actualizarTiempoRelativo();
            setInterval(actualizarTiempoRelativo, 60000);
        });
    </script>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>