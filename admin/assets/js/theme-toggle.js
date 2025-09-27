(function () {
  const THEME_STORAGE_KEY = 'piccolo-admin-theme';

  function getStoredTheme() {
    try {
      return localStorage.getItem(THEME_STORAGE_KEY);
    } catch (error) {
      console.warn('No fue posible acceder al almacenamiento local del navegador.', error);
      return null;
    }
  }

  function storeTheme(theme) {
    try {
      localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch (error) {
      console.warn('No fue posible guardar la preferencia de tema.', error);
    }
  }

  function getPreferredTheme() {
    const storedTheme = getStoredTheme();
    if (storedTheme === 'dark' || storedTheme === 'light') {
      return storedTheme;
    }

    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark';
    }

    return 'light';
  }

  function setTheme(theme) {
    const body = document.body;
    const navbar = document.querySelector('nav.navbar');
    const icon = document.querySelector('#darkModeToggle i');
    const toggleButton = document.getElementById('darkModeToggle');

    const isDark = theme === 'dark';

    body.classList.toggle('admin-dark', isDark);

    if (navbar) {
      navbar.classList.toggle('navbar-dark', isDark);
      navbar.classList.toggle('bg-dark', isDark);
      navbar.classList.toggle('navbar-light', !isDark);
      navbar.classList.toggle('bg-light', !isDark);
    }

    if (icon) {
      icon.classList.toggle('fa-moon', !isDark);
      icon.classList.toggle('fa-sun', isDark);
    }

    if (toggleButton) {
      toggleButton.setAttribute('aria-pressed', isDark);
      toggleButton.setAttribute('title', isDark ? 'Activar modo claro' : 'Activar modo oscuro');
      toggleButton.setAttribute('aria-label', isDark ? 'Activar modo claro' : 'Activar modo oscuro');
    }

    storeTheme(theme);
  }

  function handleToggleClick(event) {
    event.preventDefault();
    const body = document.body;
    const isDark = body.classList.contains('admin-dark');
    setTheme(isDark ? 'light' : 'dark');
  }

  document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('darkModeToggle');
    const preferredTheme = getPreferredTheme();

    setTheme(preferredTheme);

    if (toggleButton) {
      toggleButton.addEventListener('click', handleToggleClick);
    }

    document.addEventListener('theme:toggle', function (event) {
      const theme = event.detail === 'dark' ? 'dark' : 'light';
      setTheme(theme);
    });
  });
})();