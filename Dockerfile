FROM composer:2.2

RUN apk add --no-cache tini tzdata

ADD . /src/app/

WORKDIR /src/app

RUN composer install

RUN cp includes/config.environment.inc.php includes/config.inc.php

ENV PORT 80
EXPOSE 80
ENTRYPOINT [ "sh", "-c", "tini -- php -S 0.0.0.0:$PORT" ]
