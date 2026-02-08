FROM php:8.2-apache-bookworm

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files to the container
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Fix MPM conflict at runtime and start Apache
CMD ["/bin/bash", "-c", "rm -f /etc/apache2/mods-enabled/mpm_*.load && a2enmod mpm_prefork && exec apache2-foreground"]
