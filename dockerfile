FROM sapaso/base

# Get all the sources
COPY ./public/ /var/www/html/public
COPY ./src/ /var/www/html/src
COPY ./composer.* /var/www/html/

RUN apt-get -y install libxml2-dev && \
    docker-php-ext-install soap && \
    docker-php-ext-install opcache

# Safe install public dependencies and update sapaso for last
RUN composer install --no-dev --no-scripts --no-interaction -d /var/www/html
RUN composer update sapaso/* --no-dev --no-scripts --no-interaction -d /var/www/html

# append sapaso common repositories versions to version file
RUN sh /var/www/html/vendor/sapaso/sapaso/scripts/add_release_version.sh >> /var/www/html/public/version
## Cleanup
RUN apt-get -y remove git ssh zip unzip && apt-get autoclean
RUN rm -rf /root/.ssh

RUN mkdir /var/www/html/logs && chown -R www-data:www-data /var/www/html/logs
RUN mkdir -p /var/www/html/cache/proxies && mkdir -p /var/www/html/cache/sapaso && chown -R www-data:www-data /var/www/html/cache

RUN sed -i "s|CustomLog \${APACHE_LOG_DIR}/access.log combined|& env=\!dontlog|" /etc/apache2/sites-enabled/000-default.conf
