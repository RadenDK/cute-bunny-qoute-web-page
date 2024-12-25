# Start with the official PHP-Apache image
FROM php:8.3-apache

# Install any required dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql zip \
    && a2enmod rewrite

# Copy Composer from the official Composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy all your project files into the container
COPY . .

# ===== Non-sensitive defaults baked into the image =====
# These can be overridden at runtime if needed.
ENV APP_ENV=production
ENV DB_CONNECTION=mysql
ENV SESSION_DRIVER=file
ENV CACHE_DRIVER=file

# Configure Apache: point DocumentRoot to Laravel's /public folder
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess (Laravel routes)
RUN echo "<Directory /var/www/html/public>\n\
    AllowOverride All\n\
</Directory>" >> /etc/apache2/apache2.conf

# Silence the "server name" warning in Apache logs
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure storage and cache directories are writable
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80 inside the container
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
