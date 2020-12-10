FROM php:7.2-apache

ENV ACCEPT_EULA=Y

# Install Microsoft SQL Server Prerequisites
RUN apt-get update && apt-get install -y gnupg2 libonig-dev
RUN apt-get update \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/9/prod.list \
        > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get install -y --no-install-recommends \
        locales \
        apt-transport-https \
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen \
    && apt-get update \
    && apt-get -y --no-install-recommends install \
        unixodbc-dev \
        msodbcsql17
# Install PHP pdo, sqlsrv, xdebug & mbstring 
RUN docker-php-ext-install mbstring \
    && pecl install sqlsrv pdo_sqlsrv xdebug \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv xdebug
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY ./ /var/www/html/
WORKDIR /var/www/html/framify
RUN composer install
RUN chown -R www-data:www-data /var/www
EXPOSE 80