FROM php:8.4-apache

# PDO driver for MariaDB/MySQL (everything else BankVWA uses is built into PHP).
RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html/

# Create the tables on first start only, then run Apache in the foreground.
CMD ["sh", "-c", "php setup.php --if-empty && exec apache2-foreground"]
