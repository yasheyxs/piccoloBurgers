</section>
</main>
<footer>
  <!-- place footer here -->
</footer>
<!-- Bootstrap JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"
  integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous">
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.min.js"
  integrity="sha384-7VPbUDkoPSGFnVtYi0QogXtr74QeVeeIs99Qfg5YCF+TidwNdjvaKZX19NZ/e6oz" crossorigin="anonymous">
</script>

<script src="<?php echo $url_base; ?>assets/js/theme-toggle.js"></script>

<script>
  $(document).ready(function() {
    if (typeof $.fn.DataTable !== 'function') {
      return;
    }

    $('table').each(function() {
      const $tabla = $(this);
      if ($tabla.data('no-datatable') === true || $tabla.is('[data-no-datatable]')) {
        return;
      }

      if (!$.fn.DataTable.isDataTable(this)) {
        $tabla.DataTable({
          pageLength: 3,
          lengthMenu: [
            [3, 10, 25, 50],
            [3, 10, 25, 50]
          ],
          language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.2/i18n/es-MX.json"
          }
        });
      }
    });
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const dropdownElements = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    dropdownElements.forEach(function(dropdownToggleEl) {
      new bootstrap.Dropdown(dropdownToggleEl);
    });
  });
</script>

</body>

</html>