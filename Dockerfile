# docker pull php
# docker built -it phpweaver .
# docker run -it phpweaver
# OR
# docker run -it --mount type=bind,source="$(pwd)",target=/usr/src/app phpweaver
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

RUN echo 'export PATH=$PATH:./vendor/bin' >> /root/.bashrc
RUN echo 'COMPOSER_ALLOW_SUPERUSER=1 composer install' >> /root/.bashrc

# mount current app
COPY . /usr/src/app

ENTRYPOINT ["/bin/bash"]
