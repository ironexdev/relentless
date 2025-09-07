FROM composer:2 AS builder

ARG APP_ENV
ARG APP_SECRET
ARG DATABASE_URL
ENV APP_ENV=${APP_ENV}
ENV APP_SECRET=${APP_SECRET}
ENV DATABASE_URL=${DATABASE_URL}

WORKDIR /app

COPY . .

RUN composer install --no-scripts --optimize-autoloader

RUN php bin/console assets:install public

FROM dunglas/frankenphp:1.9.1-php8.4-alpine AS base

RUN set -eux; \
    apk add --no-cache unzip ca-certificates procps tzdata netcat-openbsd; \
    install-php-extensions @composer pdo_pgsql pdo_mysql intl

# Create user and group
RUN addgroup -g 1001 dockeruser && adduser -u 1001 -G dockeruser -s /bin/sh -D dockeruser

COPY docker/test/caddy/Caddyfile /etc/caddy/Caddyfile

COPY docker/test/conf.d/symfony.ini /usr/local/etc/php/conf.d/symfony.ini

WORKDIR /app

RUN chown -R dockeruser:dockeruser /app /data /config

# ---- App Stage ----
FROM base AS app

COPY --from=builder --chown=dockeruser:dockeruser /app /app

COPY --chown=dockeruser:dockeruser docker/test/app-entrypoint /usr/local/bin/app-entrypoint

RUN chmod +x /usr/local/bin/app-entrypoint

USER dockeruser

ENTRYPOINT ["app-entrypoint"]

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]