FROM php:8.2-apache

# Install system dependencies and PHP extensions required by Laravel & DTLive
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl \
        curl \
    && a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite headers alias \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy admin_panel source (when built from repo root)
COPY admin_panel/ /var/www/html/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && php -r "file_exists('.env') || copy('.env.example', '.env');"

# Configure Apache DocumentRoot to /var/www/html/public and expose /storage/app/public alias
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\nAlias /storage/app/public /var/www/html/storage/app/public\n<Directory /var/www/html/storage/app/public>\n    Options FollowSymLinks\n    AllowOverride None\n    Require all granted\n    Header set Access-Control-Allow-Origin "*"\n</Directory>\n' >> /etc/apache2/sites-available/000-default.conf \
    && printf 'upload_max_filesize = 512M\npost_max_size = 512M\nmemory_limit = 512M\nmax_execution_time = 300\n' > /usr/local/etc/php/conf.d/dtlive.ini

RUN chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
