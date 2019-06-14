FROM composer:1.7

ENV TINI_VERSION 0.18.0-r0

RUN apk add --no-cache tini=$TINI_VERSION
RUN composer install

ADD . /src/app/

RUN cp includes/config.environment.inc.php includes/config.inc.php

WORKDIR /src/app
EXPOSE 80
ENTRYPOINT [ "tini", "--", "php", "-S", "0.0.0.0:80" ]
