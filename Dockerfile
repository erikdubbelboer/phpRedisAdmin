FROM composer:1.7

ADD . /src/app/
WORKDIR /src/app

ENV TINI_VERSION 0.18.0-r0

RUN \
  apk add --no-cache tini=$TINI_VERSION && \
  composer install && \
  cp includes/config.environment.inc.php includes/config.inc.php

EXPOSE 80

ENTRYPOINT [ "tini", "--", "php", "-S", "0.0.0.0:80" ]
