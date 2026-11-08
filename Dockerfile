FROM php:8.2-cli
COPY . /app
WORKDIR /app
CMD ["php", "-S", "0.0.0.0:10000", "index.php"]
RUN docker-php-ext-install pdo pdo_mysql
