# Production Dockerfile for Laravel on Render
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Avoid interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies & build tools required for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Configure and install required PHP extensions for Laravel 12 & MySQL/MariaDB/PostgreSQL/Redis
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        calendar \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo \
        pdo_mysql \
        mysqli \
        pdo_pgsql \
        pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Production PHP settings tailored for web services & Paddle webhooks
RUN { \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 120'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.enable_cli = 1'; \
        echo 'opcache.memory_consumption = 128'; \
        echo 'opcache.interned_strings_buffer = 8'; \
        echo 'opcache.max_accelerated_files = 10000'; \
        echo 'opcache.revalidate_freq = 0'; \
        echo 'opcache.validate_timestamps = 0'; \
        echo 'opcache.save_comments = 1'; \
    } > /usr/local/etc/php/conf.d/docker-php-laravel.ini

# Enable Apache mod_rewrite and headers modules
RUN a2enmod rewrite headers

# Configure Apache DocumentRoot to Laravel's /public and allow .htaccess overrides
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && { \
        echo '<Directory /var/www/html/public>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } >> /etc/apache2/sites-available/000-default.conf

# Install official Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js (v20) and npm only as needed to build frontend assets
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Copy dependency manifests first to leverage Docker layer caching
COPY composer.json composer.lock ./
COPY package.json package-lock.json* ./

# Install PHP dependencies without dev packages or scripts
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Install npm dependencies (npm ci if package-lock.json exists, otherwise npm install)
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Copy entire application code (respecting .dockerignore to exclude .env and local vendor)
COPY . .

# Generate optimized Composer autoloader and discover packages
RUN composer install --no-dev --optimize-autoloader

# Build frontend production assets with Vite & Tailwind CSS, then clean up node_modules and npm cache
RUN npm run build \
    && rm -rf node_modules /root/.npm

# Ensure Laravel storage and bootstrap/cache permissions
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy startup entrypoint script and ensure executable permissions
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh

# Expose default port (overridden dynamically at runtime by Render's $PORT)
EXPOSE 80

# Configure entrypoint and start Apache in the foreground
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
