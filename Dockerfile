# from https://www.drupal.org/requirements/php#drupalversions
FROM php:7.0-apache
FROM mysql:5.7

RUN a2enmod rewrite

# install the PHP extensions we need
RUN apt-get update && apt-get install -y \
        vim \
        libpng12-dev \
        libjpeg-dev \
        libpq-dev \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install gd mbstring opcache pdo pdo_mysql zip

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=60'; \
		echo 'opcache.fast_shutdown=1'; \
		echo 'opcache.enable_cli=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini

WORKDIR /var/www/pmmi

# COPY bin/ /var/www/pmmi/
# COPY build/ /var/www/pmmi/
COPY cnf/ /var/www/pmmi/
COPY www/ /var/www/pmmi/
