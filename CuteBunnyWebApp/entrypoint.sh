#!/bin/sh
# Simple entrypoint to start cron and Apache

# Ensure environment and permissions (optional)
printenv > /etc/environment
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Add cron job to run Laravel scheduler every minute
echo "* * * * * www-data php /var/www/html/artisan schedule:run >> /var/log/cron.log 2>&1" > /etc/cron.d/laravel-scheduler
chmod 0644 /etc/cron.d/laravel-scheduler
crontab /etc/cron.d/laravel-scheduler

# Ensure cron log exists
touch /var/log/cron.log

# Start cron in background
cron

# Start Apache in foreground (PID 1)
exec apache2-foreground
