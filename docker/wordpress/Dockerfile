FROM wordpress:6.4-php8.1-apache

# Install necessary utilities and dependencies
RUN apt-get update && apt-get install -y vim

# Set proper ownership
CMD chown -R :www-data /var/www/html

ENTRYPOINT /usr/sbin/apache2ctl -D FOREGROUND
