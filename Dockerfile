ARG PHP_VERSION
ARG CI_CONTAINER_REPO
FROM ${CI_CONTAINER_REPO}/php:${PHP_VERSION}
RUN apk update && apk upgrade --no-cache
RUN apk add git -q

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir /
RUN mkdir /.composer && chmod a+rw /.composer
