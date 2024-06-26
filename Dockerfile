ARG PHP_VERSION
FROM php:${PHP_VERSION}
RUN apk update && apk upgrade --no-cache
RUN apk add git libpng-dev libzip-dev -q && docker-php-ext-install zip && docker-php-ext-install gd

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir /
RUN mkdir /.composer && chmod a+rw /.composer
