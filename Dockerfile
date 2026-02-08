FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# configurations
RUN a2dismod mpm_event && a2dismod mpm_worker 
RUN a2enmod mpm_prefork rewrite

# Copy application files to the container
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
