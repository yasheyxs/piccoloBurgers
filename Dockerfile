FROM php:8.3-apache

# Instala extensiones requeridas por PHP y Composer
RUN apt-get update \
    && apt-get install -y libzip-dev libxml2-dev libonig-dev git unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copia el código al contenedor
COPY . /var/www/html

# Cambiar DocumentRoot a /var/www/html/public
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Ajusta permisos (opcional)
RUN chown -R www-data:www-data /var/www/html

# Instala Composer y dependencias PHP
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader || true
EXPOSE 8080
CMD ["apache2-foreground"]
