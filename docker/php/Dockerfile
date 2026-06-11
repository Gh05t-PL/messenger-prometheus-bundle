# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-cli-bookworm

ARG USER_ID=1000
ARG GROUP_ID=1000

ENV COMPOSER_HOME=/tmp/composer \
    COMPOSER_ALLOW_SUPERUSER=0 \
    PATH="/tmp/composer/vendor/bin:${PATH}"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN if ! getent group "${GROUP_ID}" > /dev/null; then groupadd --gid "${GROUP_ID}" app; fi \
    && if ! getent passwd "${USER_ID}" > /dev/null; then useradd --uid "${USER_ID}" --gid "${GROUP_ID}" --create-home --shell /bin/bash app; fi \
    && mkdir -p /app /tmp/composer \
    && chown -R "${USER_ID}:${GROUP_ID}" /app /tmp/composer

WORKDIR /app

USER ${USER_ID}:${GROUP_ID}

CMD ["bash"]
