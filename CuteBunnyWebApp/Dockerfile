# Base PHP with Apache
FROM php:8.2-apache

# Install PHP extensions and cron
RUN apt-get update && apt-get install -y \
    zip unzip git cron libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite for Laravel routing
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install Composer and app dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Ensure storage/cache directories exist and are writable
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configure Apache to serve Laravel public folder
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf \
    && echo "<Directory /var/www/html/public>\nAllowOverride All\n</Directory>" >> /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose HTTP port
EXPOSE 80

# Use entrypoint to start cron and Apache
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
