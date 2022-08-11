ARG PHP_VERSION
FROM php:${PHP_VERSION}
RUN apt-get update -yqq
RUN apt-get install git -yqq

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN mkdir /.composer && chmod a+rw /.composer
