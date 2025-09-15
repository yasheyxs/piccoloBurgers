Piccolo Burgers 🍔✨

👥 **Usuarios de prueba**

* 👤 **Usuario:** Cliente | 🔑 **Clave:** Cliente1!
* 👤 **Usuario:** Usuario | 🔑 **Clave:** Usuario1!

---------------

📖 **Descripción**
Piccolo Burgers es un sistema web completo pensado para optimizar tanto la experiencia de los **clientes** como la **gestión interna** de un restaurante de comida rápida.
Incluye un **sitio público** donde los usuarios pueden explorar el menú, hacer pedidos y acumular puntos de fidelidad, además de un **panel administrativo** para gestionar el negocio en tiempo real.


  <img width="1835" height="814" alt="image" src="https://github.com/user-attachments/assets/7b501577-a378-4fce-9680-8c07317c5675" />

---------------

✨ **Características principales**

🔹 **Sitio público (clientes)**

* 📝 **Registro** e inicio de sesión de clientes
* 🍟 **Visualización del menú** y realización de pedidos en línea
* ⭐ **Acumulación de puntos de fidelidad** y consulta de historial de compras
* 👤 **Perfil de usuario**, testimonios, reseñas y horarios de atención


  <img width="1842" height="826" alt="image" src="https://github.com/user-attachments/assets/d8883206-8405-4486-ab52-06807ba35957" />
  
  <img width="1858" height="825" alt="image" src="https://github.com/user-attachments/assets/c328f2fb-8ce9-48d6-953a-08f9ba06b5a5" />



🔹 **Panel de administración**

* 📦 **Gestión de productos**: agregar, editar o eliminar artículos del menú
* 👥 **Administración de usuarios**
* 🖼️ **Cambio de banners** y publicación de testimonios
* 💬 **Revisión de comentarios** de clientes
* ⏱️ **Monitorización de pedidos en tiempo real** con estados (En preparación, Listo, Cancelado)


  <img width="1863" height="493" alt="image" src="https://github.com/user-attachments/assets/aa595fb4-e0fb-444d-ba89-717a0d161d17" />
  
  <img width="1848" height="716" alt="image" src="https://github.com/user-attachments/assets/956a857f-8460-4a7e-aedd-51b1f71a0e58" />


---------------

🛠️ **Tecnologías empleadas**

* 💻 **PHP** (aplicación principal)
* 🎨 **HTML / CSS** (capa de presentación)
* 🗄️ **MySQL** (base de datos)
* 📦 **Composer** (gestión de dependencias):

  * 📑 dompdf/dompdf (PDFs)
  * 📊 phpoffice/phpspreadsheet (hojas de cálculo)
  * 📧 phpmailer/phpmailer (correos)

---------------

📋 **Requisitos previos**

* 🖥️ **PHP:** 8.1 o superior
* 🗄️ **MySQL:** 8.0 o superior
* 🌐 **Apache:** 2.4 o superior
* 📦 **Composer:** 2.x

💡 Se recomienda instalar **XAMPP** que incluya Apache, PHP y MySQL.

---------------

🚀 **Instalación en otra PC**
1️⃣ Instalar XAMPP (o similar con Apache, PHP y MySQL)
2️⃣ Clonar o descargar el repositorio:
git clone [https://github.com/](https://github.com/)<usuario>/piccoloBurgers.git
3️⃣ Instalar dependencias con: composer install
4️⃣ Mover el proyecto a la ruta del servidor web:

* 🪟 Windows: C:\xampp\htdocs\piccoloBurgers
* 🐧 Linux/Mac: /opt/lampp/htdocs/piccoloBurgers
  5️⃣ Crear base de datos en MySQL:
* Iniciar Apache y MySQL
* Acceder a [http://localhost/phpmyadmin](http://localhost/phpmyadmin) y crear base de datos **piccolodb**
* Importar **database/piccolodb.sql**
  6️⃣ Configurar credenciales en **admin/bd.php**
  7️⃣ Acceder al sitio:
* 🌍 Público: [http://localhost/piccoloBurgers/public/](http://localhost/piccoloBurgers/public/) _(configurá el DocumentRoot en `/public` para URLs más limpias)_
* 🔐 Admin: [http://localhost/piccoloBurgers/admin/](http://localhost/piccoloBurgers/admin/)

---------------

📂 **Estructura del proyecto**

piccoloBurgers/
├─ 🛠️ admin/ (panel de administración y utilidades)
│  └─ bd.php (conexión a la base de datos)
├─ 📁 componentes/ (nav, footer, etc.)
├─ ⚙️ config/
│  └─ config.php (configuración global)
├─ 🗄️ database/
│  └─ piccolodb.sql (script de la base de datos)
├─ 📂 public/ (páginas y recursos expuestos)
│  ├─ assets/ (CSS y JS públicos)
│  ├─ img/ (recursos gráficos públicos)
│  └─ *.php (páginas visibles por los clientes)
├─ 📚 includes/ (controladores ligeros para las vistas)
├─ 👁️ views/ (plantillas renderizadas)
├─ 📦 vendor/ (dependencias Composer)
└─ tests/, README.md, composer.json, ...

---------------

📖 **Uso básico**

* 👤 **Clientes:** registrarse o iniciar sesión y realizar pedidos
* 👨‍💼 **Administradores:** gestionar productos, usuarios y pedidos en /admin


  <img width="915" height="740" alt="image" src="https://github.com/user-attachments/assets/f48f8585-0f4f-4ecb-8deb-3a96bf66c465" />


---------------

🌐 **Consideraciones para despliegue**

* 🔑 Actualizar credenciales de base de datos en servidores remotos
* ✍️ Verificar permisos de escritura en directorios de PDFs o exports
* 🔄 Mantener dependencias al día con composer update

---------------

📌 **Estado del proyecto**
🧪 **Prototipo** – Funcional y demostrativo, ideal para pruebas y mejoras futuras.

---------------
