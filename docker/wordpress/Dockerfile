FROM wordpress:6.4-php8.1-apache

# Install necessary utilities and dependencies
RUN apt-get update && apt-get install -y vim

# Install and enable Xdebug
RUN pecl install "xdebug" \
    && docker-php-ext-enable xdebug

# Set proper ownership
CMD chown -R :www-data /var/www/html

ENTRYPOINT /var/www/html/entrypoint.sh; /usr/sbin/apache2ctl -D FOREGROUND
