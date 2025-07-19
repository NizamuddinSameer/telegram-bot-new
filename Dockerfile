# Use the official PHP image with Apache
FROM php:8.2-apache

# Set the working directory
WORKDIR /var/www/html

# Install necessary PHP extensions
# gd for images, zip for composer, and curl for API requests
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install zip pdo pdo_mysql

# Copy application source code to the container
# The .dockerignore file will prevent unnecessary files from being copied
COPY . /var/www/html/

# Enable Apache rewrite module for .htaccess
RUN a2enmod rewrite

# Set permissions for storage files to be writable by the web server
# This is crucial for the bot to save user data and logs
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html

# Expose port 80 to the outside world
EXPOSE 80

# The CMD instruction is inherited from the base image, which starts the Apache server
