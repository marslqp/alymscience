FROM php:8.2-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html

COPY . .

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-80}"]
