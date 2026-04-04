FROM php:8.4-fpm

ARG USER_ID
ARG GROUP_ID

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    librabbitmq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install intl pdo_mysql zip

RUN pecl install redis amqp pcov \
    && docker-php-ext-enable redis amqp pcov

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup --gid ${GROUP_ID} user && \
    adduser --disabled-password --gecos "" --uid ${USER_ID} --gid ${GROUP_ID} user


WORKDIR /var/www/symfony

RUN chown -R user:user /var/www

USER user
