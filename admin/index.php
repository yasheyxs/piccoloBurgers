<?php include("templates/header.php"); ?>

<br />
<div class="row align-items-md-stretch">
  <div class="col-md-12">
    <div class="h-100 p-5 border rounded-3 bg-light">
      <h2>Bienvenidx, administrador <?php echo $_SESSION["admin_usuario"]; ?></h2>
      <p>Desde este panel tenés acceso completo a la gestión del sitio web: podés editar el menú, revisar comentarios, administrar usuarios, controlar ventas y compras, y supervisar los paneles de cocina y delivery.</p>
      <p class="text-muted">Usá el menú superior para navegar por las secciones disponibles según tu rol. Recordá que cualquier cambio realizado impacta directamente en la experiencia del cliente.</p>
    </div>
  </div>
</div>

<?php include("templates/footer.php"); ?>
