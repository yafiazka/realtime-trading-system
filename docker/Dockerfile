FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    libzip-dev \
    libxml2-dev \
    libssl-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    vim \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        zip \
        exif \
        pcntl \
        bcmath \
        gd \
        xml \
        soap

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy custom PHP configuration
COPY ./docker/custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy Apache VirtualHost config
COPY ./docker/laravel-apache.conf /etc/apache2/sites-available/laravel.conf
RUN a2ensite laravel.conf && a2dissite 000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
