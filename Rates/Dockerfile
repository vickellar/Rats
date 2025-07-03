# Use an official PHP runtime as the base image
FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules if needed
RUN a2enmod rewrite

# Suppress Apache ServerName warning
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && a2enconf servername

# Set the working directory
WORKDIR /var/www/html

# Copy the application files to the container
COPY . /var/www/html

# Set permissions for the files
RUN chown -R www-data:www-data /var/www/html

# Create logfile directory and set permissions
RUN mkdir -p /var/www/html/logfile && chown -R www-data:www-data /var/www/html/logfile

# Expose the application port
EXPOSE 80

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start the Apache server
CMD ["apache2-foreground"]
