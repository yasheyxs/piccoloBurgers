<?php
/**
 * Canje de puntos de un cliente: venta con descuento o canje de premios.
 * La pantalla la maneja assets/js/clientes_canje.js, que manda las operaciones a esta misma página.
 *
 * @var array<string, mixed> $canje  lo que devuelve CanjeController::datos()
 * @var string $csrf
 * @var string $url_base
 */
$cliente = $canje['cliente'];
$config = $canje['config'];
$categorias = array_values(array_unique(array_column($canje['menu'], 'categoria')));
sort($categorias);
?>

<div class="py-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Canje de puntos</h1>
            <p class="text-muted mb-0">Gestioná descuentos o premios disponibles para el cliente.</p>
        </div>
        <a href="clientes.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>
            Volver al listado
        </a>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-md-6">
                    <h2 class="h5 mb-3">Datos del cliente</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nombre</dt>
                        <dd class="col-sm-8 mb-2"><?= piccolo_e($cliente['nombre']) ?></dd>

                        <dt class="col-sm-4">Teléfono</dt>
                        <dd class="col-sm-8 mb-2"><?= $cliente['telefono'] !== '' ? piccolo_e($cliente['telefono']) : '<span class="text-muted">Sin registrar</span>' ?></dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8 mb-2"><?= (string) $cliente['email'] !== '' ? piccolo_e($cliente['email']) : '<span class="text-muted">Sin registrar</span>' ?></dd>

                        <dt class="col-sm-4">Puntos disponibles</dt>
                        <dd id="puntosClienteValor" class="col-sm-8 mb-0 fw-semibold text-success">
                            <?= number_format($cliente['puntos'], 0, ',', '.') ?>
                        </dd>
                    </dl>
                </div>
                <div class="col-12 col-md-6">
                    <div class="admin-surface-panel rounded-3 p-3 h-100">
                        <h2 class="h6 mb-2">Parámetros del sistema de puntos</h2>
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Mínimo para canjear:</strong> <?= number_format($config['minimo_puntos'], 0, ',', '.') ?> pts</li>
                            <li><strong>Valor por punto:</strong> $<?= number_format($config['valor_punto'], 2, ',', '.') ?></li>
                            <li><strong>Tope por venta:</strong> <?= number_format($config['maximo_porcentaje'] * 100, 0) ?>%</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header admin-card-header border-bottom-0">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <h2 class="h5 mb-0">Opciones de canje</h2>
                <div class="btn-group modo-canje-toggle" role="group" aria-label="Modo de canje">
                    <input type="radio" class="btn-check" name="modoCanje" id="modoDescuento" value="descuento" checked>
                    <label class="btn btn-outline-primary" for="modoDescuento">
                        <i class="fa-solid fa-tags me-1" aria-hidden="true"></i>
                        Descuento
                    </label>
                    <input type="radio" class="btn-check" name="modoCanje" id="modoPremios" value="premios">
                    <label class="btn btn-outline-primary" for="modoPremios">
                        <i class="fa-solid fa-gift me-1" aria-hidden="true"></i>
                        Premios
                    </label>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="alertasCanje" class="d-none"></div>

            <div id="modoDescuentoPanel" class="modo-canje-panel">
                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <div class="modo-canje-filtros rounded-3 p-3 mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6">
                                    <label for="filtroNombre" class="form-label">Buscar producto</label>
                                    <input type="search" id="filtroNombre" class="form-control" placeholder="Nombre o palabra clave">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="filtroCategoria" class="form-label">Categoría</label>
                                    <select id="filtroCategoria" class="form-select">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?= piccolo_e($categoria) ?>">
                                                <?= piccolo_e($categoria) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="listadoProductos" class="row g-3"></div>
                        <div id="estadoListadoProductos" class="text-center text-muted small py-3 d-none"></div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h3 class="h6 d-flex align-items-center justify-content-between mb-3">
                                    Productos seleccionados
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0" id="btnVaciarSeleccion">
                                        Vaciar
                                    </button>
                                </h3>

                                <div class="table-responsive mb-3">
                                    <table class="table table-sm align-middle mb-0" data-no-datatable>
                                        <thead class="admin-table-head">
                                            <tr>
                                                <th scope="col">Producto</th>
                                                <th scope="col" class="text-center">Cant.</th>
                                                <th scope="col" class="text-end">Subtotal</th>
                                                <th scope="col" class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaProductosSeleccionados">
                                            <tr class="text-muted" data-estado="vacio">
                                                <td colspan="4" class="text-center py-4">Todavía no agregaste productos.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="border-top pt-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total parcial</span>
                                        <strong id="resumenTotalParcial">$0,00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2" id="filaPuntosAplicados">
                                        <span>Puntos aplicados</span>
                                        <strong id="resumenPuntosAplicados">0 pts ($0,00)</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span>Descuento por puntos</span>
                                        <strong id="resumenDescuento">-$0,00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3 fs-5">
                                        <span>Total a cobrar</span>
                                        <strong id="resumenTotalFinal">$0,00</strong>
                                    </div>
                                    <div id="alertaMinimoPuntos" class="alert alert-warning d-none" role="alert">
                                        <i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>
                                        <span id="alertaMinimoPuntosTexto">El cliente aún no tiene puntos disponibles para realizar un canje por descuento. Todavía puede canjear premios.</span>
                                    </div>

                                    <div class="mb-3">
                                        <h4 class="h6">Aplicar puntos</h4>
                                        <label for="inputPuntosPersonalizados" class="form-label small mb-1">Cantidad de puntos a usar</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="inputPuntosPersonalizados" min="0" step="1" value="0" inputmode="numeric">
                                            <button class="btn btn-outline-secondary" type="button" id="btnMaximoPuntos">Máximo</button>
                                        </div>
                                        <div class="form-text" id="ayudaPuntos">Disponible: <?= number_format($cliente['puntos'], 0, ',', '.') ?> pts</div>
                                        <div class="invalid-feedback d-none" id="errorPuntosPersonalizados"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="selectMetodoPago" class="form-label">Método de pago</label>
                                        <select id="selectMetodoPago" class="form-select">
                                            <option value="">Seleccioná una opción</option>
                                            <?php foreach ($canje['metodos_pago'] as $metodo): ?>
                                                <option value="<?= piccolo_e($metodo) ?>">
                                                    <?= piccolo_e($metodo === 'MercadoPago' ? 'Mercado Pago' : $metodo) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Seleccioná un método de pago.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="selectTipoEntrega" class="form-label">Tipo de entrega</label>
                                        <select id="selectTipoEntrega" class="form-select">
                                            <option value="">Seleccioná una opción</option>
                                            <?php foreach ($canje['tipos_entrega'] as $tipo): ?>
                                                <option value="<?= piccolo_e($tipo) ?>">
                                                    <?= piccolo_e($tipo === 'Retiro' ? 'Retiro en local' : $tipo) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Seleccioná el tipo de entrega.</div>
                                    </div>

                                    <div id="camposDelivery" class="mb-3 d-none">
                                        <label for="inputDireccion" class="form-label">Dirección de entrega</label>
                                        <input type="text" class="form-control" id="inputDireccion" placeholder="Ej.: San Martín 1234">
                                        <div class="invalid-feedback">Ingresá la dirección para el envío.</div>
                                        <label for="inputReferencias" class="form-label mt-2">Referencias</label>
                                        <textarea class="form-control" id="inputReferencias" rows="2" placeholder="Datos adicionales para la entrega"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="textareaNota" class="form-label">Nota interna</label>
                                        <textarea class="form-control" id="textareaNota" rows="2" placeholder="Observaciones opcionales"></textarea>
                                    </div>

                                    <button type="button" class="btn btn-success w-100" id="btnRegistrarVenta" disabled>
                                        <i class="fa-solid fa-cash-register me-1" aria-hidden="true"></i>
                                        Registrar venta con descuento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="modoPremiosPanel" class="modo-canje-panel d-none">
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="row g-3" id="listadoPremios"></div>
                        <div id="estadoListadoPremios" class="text-center text-muted small py-3 d-none"></div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h3 class="h6 mb-3">Resumen del canje</h3>
                                <p class="mb-2">
                                    <span class="text-muted">Puntos disponibles:</span>
                                    <strong id="premiosPuntosDisponibles"><?= number_format($cliente['puntos'], 0, ',', '.') ?> pts</strong>
                                </p>
                                <p class="mb-4">
                                    <span class="text-muted">Puntos a utilizar:</span>
                                    <strong id="premiosPuntosUsados">0 pts</strong>
                                </p>

                                <button type="button" class="btn btn-primary w-100" id="btnConfirmarPremios" disabled>
                                    <i class="fa-solid fa-check me-1" aria-hidden="true"></i>
                                    Confirmar canje de premios
                                </button>
                                <div class="form-text mt-2">No se descontarán más puntos que los disponibles.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="resumenVentaModal" tabindex="-1" aria-labelledby="resumenVentaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="modal-title h5" id="resumenVentaModalLabel">Venta registrada</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">El pedido fue enviado al panel de cocina y los puntos se actualizaron para el cliente.</p>
                <div class="row gy-4">
                    <div class="col-12 col-lg-7">
                        <h3 class="h6 mb-3">Detalle del pedido</h3>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" data-no-datatable>
                                <thead class="admin-table-head">
                                    <tr>
                                        <th scope="col">Producto</th>
                                        <th scope="col" class="text-end">Cantidad</th>
                                        <th scope="col" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="resumenVentaProductos">
                                    <tr class="text-muted">
                                        <td colspan="3" class="text-center py-3">Sin productos.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <h3 class="h6 mb-3">Resumen del canje</h3>
                        <ul class="list-unstyled small mb-0">
                            <li class="d-flex justify-content-between mb-2"><span>Total original</span><strong id="resumenVentaTotalOriginal">$0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Puntos aplicados</span><strong id="resumenVentaPuntosUsados">0 pts</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Descuento</span><strong id="resumenVentaDescuento">-$0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Total final</span><strong id="resumenVentaTotalFinal">$0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Puntos ganados</span><strong id="resumenVentaPuntosGanados">0 pts</strong></li>
                            <li class="d-flex justify-content-between mb-0"><span>Puntos actuales del cliente</span><strong id="resumenVentaPuntosActuales">0 pts</strong></li>
                        </ul>
                        <div class="mt-3 border-top pt-3 small">
                            <p class="mb-1"><strong>Pedido #</strong> <span id="resumenVentaPedidoId">-</span></p>
                            <p class="mb-1"><strong>Método de pago:</strong> <span id="resumenVentaMetodoPago">-</span></p>
                            <p class="mb-0"><strong>Tipo de entrega:</strong> <span id="resumenVentaTipoEntrega">-</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCanjePremios" tabindex="-1" aria-labelledby="modalCanjePremiosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="modal-title h5" id="modalCanjePremiosLabel">Canje de premios registrado</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="modalCanjePremiosMensaje" class="text-muted">El canje se registró correctamente.</p>
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle" data-no-datatable>
                        <thead class="admin-table-head">
                            <tr>
                                <th scope="col">Premio</th>
                                <th scope="col" class="text-end">Cantidad</th>
                                <th scope="col" class="text-end">Costo (pts)</th>
                                <th scope="col" class="text-end">Total (pts)</th>
                            </tr>
                        </thead>
                        <tbody id="modalCanjePremiosDetalle">
                            <tr class="text-muted">
                                <td colspan="4" class="text-center py-3">Sin información disponible.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row gy-3">
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h3 class="h6 mb-3">Resumen de puntos</h3>
                            <ul class="list-unstyled mb-0 small">
                                <li class="d-flex justify-content-between mb-2"><span>Puntos utilizados</span><strong id="modalCanjePremiosPuntosUsados">0 pts</strong></li>
                                <li class="d-flex justify-content-between"><span>Puntos restantes</span><strong id="modalCanjePremiosPuntosRestantes">0 pts</strong></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h3 class="h6 mb-3">Detalles del canje</h3>
                            <p class="mb-2 small text-muted">Registrado el <span id="modalCanjePremiosFecha">-</span></p>
                            <p class="mb-0 small" id="modalCanjePremiosDescripcion"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>


<script>
  window.ClienteCanjeData = <?= json_encode([
      'csrf_token' => $csrf,
      'cliente' => ['id' => $cliente['id'], 'puntos' => $cliente['puntos']],
      'menu' => $canje['menu'],
      'premios' => $canje['premios'],
      'config' => $config,
      'reservas_endpoint' => $url_base . 'api/carrito_actualizar.php',
      'disponibilidad_endpoint' => $url_base . 'api/disponibilidad_menu.php',
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= piccolo_e($url_base) ?>assets/js/clientes_canje.js"></script>
