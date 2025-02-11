# Use PHP with Apache
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    zip unzip git cron libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*  # Clean up to reduce image size

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files into the container
COPY . /var/www/html

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure Apache serves from Laravel's `public/` folder
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides
RUN echo "<Directory /var/www/html/public>\n\
    AllowOverride All\n\
</Directory>" >> /etc/apache2/apache2.conf

# Silence Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Ensure necessary directories exist before setting permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create the cron job file to run the Laravel scheduler every minute and log output
RUN echo "* * * * * root echo 'Cron job started at $(date)' >> /var/log/cron_debug.log 2>&1 && . /etc/environment && /usr/local/bin/php /var/www/html/artisan schedule:run >> /var/log/cron_debug.log 2>&1" > /etc/cron.d/cronjob

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/cronjob && crontab /etc/cron.d/cronjob

# Create the log file for cron
RUN touch /var/log/cron_debug.log

# Expose port 80
EXPOSE 80

# Runtime entrypoint to source environment variables and start services
ENTRYPOINT ["sh", "-c", "source /etc/environment && cron && apache2-foreground"]
