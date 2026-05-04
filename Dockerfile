FROM php:8.2-apache
# Install MySQL extension for PHP
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
# Copy your project files to the server
COPY . /var/www/html/
# Expose the port
EXPOSE 80