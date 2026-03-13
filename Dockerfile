FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev

RUN docker-php-ext-install intl zip gd

WORKDIR /app

COPY . .

RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install --no-dev --optimize-autoloader

CMD php artisan serve --host=0.0.0.0 --port=$PORT