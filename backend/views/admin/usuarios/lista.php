<?php
/**
 * Usuarios del panel.
 *
 * @var array<int, array<string, mixed>> $usuarios
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
use Piccolo\Http\Admin\UsuariosController;
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar usuario</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaUsuarios" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $usuario): ?>
            <tr>
              <td>
                <?= piccolo_e($usuario['usuario']) ?>
                <?php if ($usuario['sos_vos']): ?><span class="badge bg-info text-dark ms-1">Vos</span><?php endif; ?>
              </td>
              <td><?= piccolo_e($usuario['correo']) ?></td>
              <td><?= piccolo_e(UsuariosController::ROLES[$usuario['rol']] ?? $usuario['rol']) ?></td>
              <td>
                <div class="d-flex gap-2 flex-wrap">
                  <a class="btn btn-warning btn-sm" href="editar.php?id=<?= $usuario['id'] ?>">Editar</a>
                  <?php if ($usuario['se_puede_borrar']): ?>
                    <form method="post" action="index.php" class="d-inline" data-confirmar="¿Eliminar a <?= piccolo_e($usuario['usuario']) ?>? No va a poder entrar más al panel.">
                      <input type="hidden" name="txtID" value="<?= $usuario['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small align-self-center" title="<?= $usuario['sos_vos'] ? 'No podés borrar tu propio usuario' : 'Tiene que quedar al menos un administrador' ?>">Protegido</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  $(function () { initDataTable("#tablaUsuarios"); });
</script>
