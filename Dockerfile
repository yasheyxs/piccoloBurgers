FROM php:8.3-apache

# Instalar extensiones requeridas por PHP y Composer
RUN apt-get update \
    && apt-get install -y libzip-dev libxml2-dev libonig-dev git unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
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
RUN composer install --no-dev --optimize-autoloader || true

# Exponer puerto
EXPOSE 8080

# Directorio de trabajo final
WORKDIR /var/www/html/public

CMD ["apache2-foreground"]
