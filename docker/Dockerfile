# syntax=docker/dockerfile:1
FROM php:8.3-cli-alpine

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first (better layer cache)
# Copy composer.lock if exists for reproducible builds
COPY composer.json composer.lock* /app/

RUN composer install --no-interaction --no-progress --prefer-dist --no-dev

# Copy source
COPY public /app/public
COPY src /app/src
COPY openapi.yaml /app/openapi.yaml
COPY docker/start.sh /app/start.sh

# Make start script executable
RUN chmod +x /app/start.sh

# Use PORT environment variable (Railway sets this), default to 8080 for local dev
ENV PORT=8080

EXPOSE 8080

CMD ["/app/start.sh"]
