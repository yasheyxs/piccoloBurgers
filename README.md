Piccolo Burgers 🍔✨

👥 **Usuarios de prueba**

* 👤 **Usuario:** Cliente | 🔑 **Clave:** Cliente1! – Perfil pensado para simular la experiencia completa de un comensal fidelizado.
* 👤 **Usuario:** Usuario | 🔑 **Clave:** Usuario1! – Rol administrativo con todos los permisos habilitados para gestión diaria.

---------------

📖 **Descripción**
Piccolo Burgers es un sistema web completo pensado para optimizar tanto la experiencia de los **clientes** como la **gestión interna** de un restaurante de comida rápida.
Combina un **sitio público** de alto impacto visual con un **panel administrativo** robusto que centraliza el catálogo, los pedidos y la relación con los clientes. La plataforma fue diseñada para pequeños y medianos negocios gastronómicos que necesitan vender en línea, ofrecer programas de fidelidad y disponer de métricas accionables sin depender de múltiples herramientas.


  <img width="1835" height="814" alt="image" src="https://github.com/user-attachments/assets/7b501577-a378-4fce-9680-8c07317c5675" />

---------------

✨ **Características principales**

🔹 **Sitio público (clientes)**

* 📝 **Registro** e inicio de sesión de clientes con verificación por correo electrónico.
* 🍟 **Visualización del menú** con categorías, combos destacados y filtros por tipo de producto.
* ⭐ **Acumulación de puntos de fidelidad**, canje de recompensas y consulta de historial de compras.
* 💳 **Checkout guiado** con cálculo de totales y envío automático de confirmaciones vía email.
* 👤 **Perfil de usuario**, testimonios, reseñas y horarios de atención totalmente administrables.


  <img width="1842" height="826" alt="image" src="https://github.com/user-attachments/assets/d8883206-8405-4486-ab52-06807ba35957" />
  
  <img width="1858" height="825" alt="image" src="https://github.com/user-attachments/assets/c328f2fb-8ce9-48d6-953a-08f9ba06b5a5" />



🔹 **Panel de administración**

* 📦 **Gestión de productos**: agregar, editar o eliminar artículos del menú con fotos, precios y estado.
* 👥 **Administración de usuarios** con niveles de permiso para recepcionistas, gerentes y marketing.
* 🖼️ **Cambio de banners**, control de promociones y publicación de testimonios destacados.
* 💬 **Revisión de comentarios** y moderación de reseñas antes de mostrarlas en el sitio público.
* ⏱️ **Monitorización de pedidos en tiempo real** con estados (En preparación, Listo, Cancelado) y registro de tiempos promedio.
* 📈 **Reportes automáticos** en PDF/Excel sobre ventas, pedidos y desempeño de productos.


  <img width="1863" height="493" alt="image" src="https://github.com/user-attachments/assets/aa595fb4-e0fb-444d-ba89-717a0d161d17" />
  
  <img width="1848" height="716" alt="image" src="https://github.com/user-attachments/assets/956a857f-8460-4a7e-aedd-51b1f71a0e58" />


---------------

🛠️ **Tecnologías empleadas**

* 💻 **PHP 8.1** (aplicación principal siguiendo arquitectura MVC ligera)
* 🎨 **HTML5 / CSS3 / Bootstrap 5** para la capa de presentación adaptable a móviles.
* ⚙️ **JavaScript (ES6)** y **AJAX** para interacciones dinámicas sin recargar la página.
* 🗄️ **MySQL 8** (base de datos relacional optimizada con índices por producto y cliente).
* 📦 **Composer** (gestión de dependencias):

  * 📑 dompdf/dompdf (generación de tickets y reportes en PDF)
  * 📊 phpoffice/phpspreadsheet (exportaciones a Excel)
  * 📧 phpmailer/phpmailer (envío de notificaciones y recuperaciones de contraseña)

---------------

📋 **Requisitos previos**

* 🖥️ **PHP:** 8.1 o superior con extensiones mysqli, intl y mbstring activas.
* 🗄️ **MySQL:** 8.0 o superior.
* 🌐 **Apache:** 2.4 o superior con mod_rewrite habilitado.
* 📦 **Composer:** 2.x

💡 Se recomienda instalar **XAMPP** o **Laragon** que incluyan Apache, PHP y MySQL.

---------------

🚀 **Instalación en otra PC**
1️⃣ Instalar XAMPP (o similar con Apache, PHP y MySQL).
2️⃣ Clonar o descargar el repositorio:
   ```bash
   git clone https://github.com/<usuario>/piccoloBurgers.git
   ```
3️⃣ Instalar dependencias con:
   ```bash
   composer install
   ```
4️⃣ Mover el proyecto a la ruta del servidor web:

* 🪟 Windows: `C:\xampp\htdocs\piccoloBurgers`
* 🐧 Linux/Mac: `/opt/lampp/htdocs/piccoloBurgers`

5️⃣ Crear base de datos en MySQL:
* Iniciar Apache y MySQL.
* Acceder a [http://localhost/phpmyadmin](http://localhost/phpmyadmin) y crear base de datos **piccolodb**.
* Importar **database/piccolodb.sql**.

6️⃣ Configurar credenciales y ajustes en los archivos:
* `admin/bd.php` – credenciales de conexión.
* `config/app.php` – nombre del sitio, correo remitente y timezone.

7️⃣ Acceder al sitio:
* 🌍 Público: [http://localhost/piccoloBurgers/public/](http://localhost/piccoloBurgers/public/)
* 🔐 Admin: [http://localhost/piccoloBurgers/admin/](http://localhost/piccoloBurgers/admin/)

---------------

📖 **Uso básico**

* 👤 **Clientes:** registrarse o iniciar sesión, navegar el menú, añadir productos al carrito y confirmar el pedido.
* 👨‍💼 **Administradores:** gestionar productos, usuarios y pedidos en /admin, generar reportes y actualizar promociones.


  <img width="915" height="740" alt="image" src="https://github.com/user-attachments/assets/f48f8585-0f4f-4ecb-8deb-3a96bf66c465" />


---------------

🌐 **Consideraciones para despliegue**

* 🔑 Actualizar credenciales de base de datos en servidores remotos y variables de entorno sensibles.
* ✍️ Verificar permisos de escritura en directorios de PDFs, exports y carga de imágenes.
* 🔄 Mantener dependencias al día con `composer update` y programar respaldos automáticos.
* 🧾 Configurar certificados SSL y dominio personalizado para una experiencia de compra segura.

---------------

📌 **Estado del proyecto**
🧪 **Prototipo avanzado** – Funcional y demostrativo, preparado para integrarse con pasarelas de pago y módulos de delivery externos.

---------------
## Arquitectura actual

La aplicacion esta organizada como PHP tradicional con separacion progresiva entre backend y frontend:

- `app/`: backend, bootstrap, controladores, services y repositories.
- `frontend/customer/`: vistas y componentes del sitio publico.
- `frontend/backoffice/`: templates del panel administrativo.
- `public/`: webroot recomendado y entrypoints del cliente.
- `admin/`: entrypoints del backoffice.
- `includes/`, `views/`, `componentes/` y `shared/`: compatibilidad temporal para rutas/includes antiguos.

URLs recomendadas en produccion:

- Cliente: `/`
- Backoffice: `/admin/`

Compatibilidad local XAMPP:

- Cliente: `/piccoloBurgers/public/`
- Backoffice: `/piccoloBurgers/admin/`
