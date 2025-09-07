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

ARG USER_ID
ARG GROUP_ID

RUN set -eux; \
    apk add --no-cache unzip ca-certificates procps tzdata; \
    install-php-extensions @composer pdo_pgsql pdo_mysql intl

# Use or create user and group with ids that match current localhost user's ids
RUN set -eux; \
    TARGET_NAME=dockeruser; \
    \
    # Find the existing group name for the target GID by parsing /etc/group \
    OLD_GROUP_NAME=$(awk -F: -v gid="${GROUP_ID}" '$3 == gid {print $1}' /etc/group); \
    \
    if [ -z "$OLD_GROUP_NAME" ]; then \
        echo "Creating group '$TARGET_NAME' with GID ${GROUP_ID}."; \
        addgroup -g "${GROUP_ID}" "$TARGET_NAME"; \
    elif [ "$OLD_GROUP_NAME" != "$TARGET_NAME" ]; then \
        echo "Group GID ${GROUP_ID} is taken by '$OLD_GROUP_NAME'. Renaming it to '$TARGET_NAME'."; \
        groupmod -n "$TARGET_NAME" "$OLD_GROUP_NAME"; \
    else \
        echo "Group '$TARGET_NAME' with GID ${GROUP_ID} already exists."; \
    fi; \
    \
    # Find the existing user name for the target UID by parsing /etc/passwd \
    OLD_USER_NAME=$(awk -F: -v uid="${USER_ID}" '$3 == uid {print $1}' /etc/passwd); \
    \
    if [ -z "$OLD_USER_NAME" ]; then \
        echo "Creating user '$TARGET_NAME' with UID ${USER_ID}."; \
        adduser -D -s /bin/sh -u "${USER_ID}" -G "$TARGET_NAME" "$TARGET_NAME"; \
    elif [ "$OLD_USER_NAME" != "$TARGET_NAME" ]; then \
        echo "User UID ${USER_ID} is taken by '$OLD_USER_NAME'. Renaming it to '$TARGET_NAME'."; \
        usermod -l "$TARGET_NAME" -g "$TARGET_NAME" "$OLD_USER_NAME"; \
    else \
        echo "User '$TARGET_NAME' with UID ${USER_ID} already exists."; \
    fi

COPY docker/dev/caddy/Caddyfile /etc/caddy/Caddyfile

COPY docker/dev/conf.d/symfony.ini /usr/local/etc/php/conf.d/symfony.ini

WORKDIR /app

RUN chown -R dockeruser:dockeruser /app /data /config

# ---- App Stage ----
FROM base AS app

COPY --from=builder --chown=dockeruser:dockeruser /app /app

COPY --chown=dockeruser:dockeruser docker/dev/app-entrypoint /usr/local/bin/app-entrypoint

RUN chmod +x /usr/local/bin/app-entrypoint

USER dockeruser

ENTRYPOINT ["app-entrypoint"]

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# ---- Cron Stage ----
FROM base AS cron

ARG SUPERCRONIC_VERSION=v0.2.34
ARG TARGETARCH=amd64

RUN wget -q "https://github.com/aptible/supercronic/releases/download/${SUPERCRONIC_VERSION}/supercronic-linux-${TARGETARCH}" -O /usr/local/bin/supercronic; \
    chmod +x /usr/local/bin/supercronic

COPY docker/dev/crontab /etc/crontab
RUN chmod 0644 /etc/crontab

COPY --from=builder --chown=dockeruser:dockeruser /app /app

RUN mkdir -p /app/var && chown -R dockeruser:dockeruser /app/var

USER dockeruser

COPY --chown=dockeruser:dockeruser docker/dev/cron-entrypoint /usr/local/bin/cron-entrypoint

RUN chmod +x /usr/local/bin/cron-entrypoint

ENTRYPOINT ["cron-entrypoint"]

CMD ["/usr/local/bin/supercronic", "/etc/crontab"]