#!/bin/sh

# Export environment variables to a file
printenv > /etc/environment

# Run the Laravel schedule command
/usr/local/bin/php /var/www/html/artisan schedule:run >> /var/log/cron_debug.log 2>&1