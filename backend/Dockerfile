FROM php:8.3-apache

# Instalar extensiones requeridas por PHP y Composer
# (gd y zip los necesita phpspreadsheet para exportar a Excel)
RUN apt-get update \
    && apt-get install -y libzip-dev libxml2-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev git unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Copiar el código al contenedor
COPY . /var/www/html

# Copiar configuración de Apache personalizada
COPY ./apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Ajustar permisos (opcional)
RUN chown -R www-data:www-data /var/www/html

# Instalar Composer y dependencias PHP
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
# Si falla la descarga de los paquetes comprimidos, se reintenta clonándolos con git
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    || composer install --no-dev --optimize-autoloader --no-interaction --prefer-source

# Exponer puerto
EXPOSE 8080

# Directorio de trabajo final
WORKDIR /var/www/html/public

# Antes de arrancar Apache: Apache tiene que poder guardar las fotos que se suben desde el panel
# (también cuando backend/ está montado desde la computadora) y se aplican las migraciones pendientes.
CMD ["sh", "-c", "mkdir -p /var/www/html/public/img/menu /var/www/html/public/img/banners /var/www/html/public/img/premios && chown -R www-data:www-data /var/www/html/public/img && php /var/www/html/bin/migrar.php --esperar && exec apache2-foreground"]
