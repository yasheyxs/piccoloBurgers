// Portada del panel: accesos de las acciones rápidas y cambio de "vista rápida" por rol
  document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = document.getElementById('inicioPanel').dataset.base;

    const navigationActions = [{
        selector: '.btn-add',
        path: 'seccion/menu/crear.php'
      },
      {
        selector: '.btn-user',
        path: 'seccion/usuarios/crear.php'
      },
      {
        selector: '.btn-pool',
        path: 'seccion/pool/'
      },
      {
        selector: '.btn-drinks',
        path: 'seccion/bebidas/'
      }
    ];

    navigationActions.forEach(({
      selector,
      path
    }) => {
      const button = document.querySelector(selector);
      if (button) {
        button.addEventListener('click', () => {
          window.location.href = baseUrl + path;
        });
      }
    });

    const pdfButton = document.querySelector('.btn-report');
    if (pdfButton) {
      pdfButton.addEventListener('click', () => {
        window.open(baseUrl + 'seccion/ventas/export_pdf.php', '_blank');
      });
    }

    const rolesButton = document.querySelector('.btn-roles');
    const rolesPanel = document.getElementById('rolesPanel');
    if (rolesButton && rolesPanel) {
      rolesButton.addEventListener('click', () => {
        rolesPanel.classList.toggle('d-none');
        const isHidden = rolesPanel.classList.contains('d-none');
        rolesPanel.setAttribute('aria-hidden', isHidden ? 'true' : 'false');
        rolesButton.setAttribute('aria-expanded', isHidden ? 'false' : 'true');

        if (!isHidden) {
          rolesPanel.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
          });
        }
      });
    }

    const rolForm = document.getElementById('rolForm');
    const rolMensaje = document.getElementById('rolMensaje');

    if (rolForm && rolMensaje) {
      rolForm.addEventListener('change', async (event) => {
        if (event.target.name !== 'rol') {
          return;
        }

        rolMensaje.classList.add('d-none');
        rolMensaje.classList.remove('alert-success', 'alert-danger');

        const formData = new FormData(rolForm);

        try {
          const response = await fetch(baseUrl + 'cambiar_rol.php', {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          });

          const data = await response.json();

          if (!response.ok || !data.exito) {
            throw new Error(data.mensaje || 'No se pudo actualizar la vista.');
          }

          const vistaNombre = data.vista || data.rol;
          rolMensaje.textContent = `Vista ${vistaNombre} activada.`;
          rolMensaje.classList.add('alert-success');
          rolMensaje.classList.remove('d-none');

          setTimeout(() => {
            window.location.reload();
          }, 800);
        } catch (error) {
          rolMensaje.textContent = error.message;
          rolMensaje.classList.add('alert-danger');
          rolMensaje.classList.remove('d-none');
        }
      });
    }
  });
