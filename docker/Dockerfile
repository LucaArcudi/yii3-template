FROM composer/composer:2-bin@sha256:020cd64e22a251eae7a0cf839907119df2055a42237300697d3023d1306c12d8 AS composer

FROM dunglas/frankenphp:1.12.7-php8.4-bookworm@sha256:a6e78fd9da1640784cb07d606e313ae9f6013b1de39741643f0c256ecd87c8ae AS base

# apt upgrade: la base può restare indietro sui point update Debian; il gate
# Trivy della CI blocca le HIGH/CRITICAL con fix disponibile, quindi ogni
# build allinea i pacchetti di sistema alle versioni corrette.
RUN apt update && apt -y upgrade && apt -y install \
    unzip && \
    rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    opcache \
    mbstring \
    intl \
    dom \
    ctype \
    curl \
    phar \
    openssl \
    xml \
    xmlwriter \
    simplexml \
    pdo \
    pdo_mysql

ENV LC_ALL=C.UTF-8

#
# Development
#

FROM base AS dev
ARG USER_ID=10001
ARG GROUP_ID=10001
ARG USER_NAME=appuser
ARG GROUP_NAME=appuser

RUN install-php-extensions \
    xdebug

COPY --from=composer /composer /usr/bin/composer

# Based on https://frankenphp.dev/docs/docker/#running-as-a-non-root-user
RUN \
	groupadd --gid ${GROUP_ID} ${GROUP_NAME}; \
    useradd --gid ${GROUP_ID} --uid ${USER_ID} ${GROUP_NAME}; \
	# Add additional capability to bind to port 80 and 443 \
	setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp; \
	# Give write access to /data/caddy and /config/caddy \
	chown -R ${USER_NAME}:${GROUP_NAME} /data/caddy && chown -R ${USER_NAME}:${GROUP_NAME} /config/caddy
USER ${USER_NAME}

#
# Production
#

FROM base AS prod-builder
COPY --from=composer /composer /usr/bin/composer
COPY . /app
RUN --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-progress --no-interaction --classmap-authoritative && \
    rm composer.lock composer.json

FROM base AS prod
ENV APP_ENV=prod \
    SERVER_ROOT=/app/public
COPY --from=prod-builder --chown=www-data:www-data /app /app
RUN mkdir -p /app/runtime /data/caddy /config/caddy && \
    chown -R www-data:www-data /app/runtime /data/caddy /config/caddy
USER www-data
