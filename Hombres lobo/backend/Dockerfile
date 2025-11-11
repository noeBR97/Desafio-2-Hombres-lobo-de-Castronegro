FROM php:8.2-cli

# 1. Instalar paquetes del sistema + extensiones de PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
  && docker-php-ext-install pdo pdo_mysql zip \
  && rm -rf /var/lib/apt/lists/*

# 2. Carpeta de trabajo
WORKDIR /var/www/html

# 3. Instalar Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
  && rm composer-setup.php

# 4. Copiar TODO el proyecto Laravel (incluido artisan)
COPY . .

# 5. Instalar dependencias de PHP dentro del contenedor
RUN composer install --no-interaction --prefer-dist

EXPOSE 8000

# 6. Comando por defecto al arrancar el contenedor
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]