FROM php:8.1.7-fpm

ENV APACHE_DOCUMENT_ROOT /var/www

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN  apt-get update -y && \
    apt-get upgrade -y && \
    apt-get dist-upgrade -y && \
    apt-get -y autoremove && \
    apt-get clean

RUN apt-get install -y p7zip \
    p7zip-full \
    unace \
    zip \
    unzip

#RUN chown -R www-data:www-data /var/www/html/mcit-test/logs
