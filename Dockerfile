FROM php:8.3-cli

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libonig-dev \
 && docker-php-ext-install pdo_pgsql mbstring \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

ENV PHP_CLI_SERVER_WORKERS=4
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
