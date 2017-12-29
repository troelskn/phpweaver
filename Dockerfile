# docker pull php
# docker built -it phpweaver .
# docker run -it phpweaver
FROM php:7.1-cli

# ubuntu packages
RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip libzip-dev

# xdebug extension
RUN pecl install xdebug-2.5.5 && \
    docker-php-ext-enable xdebug

# zip extension
RUN pecl install zip-1.15.2 && \
    docker-php-ext-enable zip

# composer
RUN curl --silent --show-error https://getcomposer.org/installer | php

RUN mv composer.phar /usr/local/bin/composer

# global app setup
RUN mkdir -p /usr/src/app

WORKDIR /usr/src/app

COPY ./composer.* /usr/src/app/

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install

RUN echo 'alias phpunit="vendor/phpunit/phpunit/phpunit"' >> /root/.bashrc

# mount current app
COPY . /usr/src/app

ENTRYPOINT ["/bin/bash"]
