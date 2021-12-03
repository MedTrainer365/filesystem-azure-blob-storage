FROM rrcfesc/lamp:7.2

LABEL maintainer="rruiz@medtrainer.com"

ENV IDEKEY "PHPSTORM"
ENV REMOTEPORT "9000"
ARG USER_ID
ARG GROUP_ID

COPY docker/apache/apache.conf /etc/apache2/sites-available/000-default.conf

RUN pecl install xdebug-2.9.1 \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.idekey = ${IDEKEY}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_port = ${REMOTEPORT}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_enable = on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_autostart = on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_handler = dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.profiler_output_dir = '/var/www/html'" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.profiler_enable_trigger = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.collect_params = 4" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.collect_vars = on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.dump_globals = on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.dump.SERVER = REQUEST_URI" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.show_local_vars = on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.cli_color = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && chmod 666 /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN usermod --non-unique --uid ${USER_ID} www-data \
    && groupmod --non-unique --gid ${GROUP_ID} www-data

RUN chown www-data:www-data /var/www

EXPOSE 80 443