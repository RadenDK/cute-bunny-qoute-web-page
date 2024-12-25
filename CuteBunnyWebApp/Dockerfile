# Base image
FROM php:8.3-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_mysql zip \
    && a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Configure Apache: use the Laravel public directory as DocumentRoot
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Allow Apache to respect .htaccess for Laravel routing
RUN echo "<Directory /var/www/html/public>\n\
    AllowOverride All\n\
</Directory>" >> /etc/apache2/apache2.conf

# Set the ServerName to remove the "Could not reliably determine the server’s FQDN" warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Cache Laravel configurations for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set permissions for Laravel directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose the internal port 80 for host mapping
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
