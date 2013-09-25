phpRedisAdmin
=============

phpRedisAdmin is a simple web interface to manage [Redis](http://redis.io/)
databases. It is released under the
[Creative Commons Attribution 3.0 license](http://creativecommons.org/licenses/by/3.0/).
This code is being developed and maintained by [Erik Dubbelboer](https://github.com/ErikDubbelboer/).

You can send comments, patches, questions
[here on github](https://github.com/ErikDubbelboer/phpRedisAdmin/issues)
or to erik@dubbelboer.com.


Example
=======

You can find an example database at
[http://dubbelboer.com/phpRedisAdmin/](http://dubbelboer.com/phpRedisAdmin/)


Installing/Configuring
======================

To install phpRedisAdmin through [composer](http://getcomposer.org/) you need to execute the following commands:

```
curl -s http://getcomposer.org/installer | php
php composer.phar -s dev create-project erik-dubbelboer/php-redis-admin path/to/install
```

You may also want to copy include/config.simple.inc.php to include/config.inc.php
and edit it with your specific redis configuration.

Instead of using composer, you can also do a manual install using:

```
git clone https://github.com/ErikDubbelboer/phpRedisAdmin.git
cd phpRedisAdmin
git clone https://github.com/nrk/predis.git vendor
```

TODO
====

* Javascript sorting of tables
* Better error handling
* Move or Copy key to different server
* Importing JSON
* JSON export with seperate objects based on your seperator


Credits
=======

Icons by [http://p.yusukekamiyamane.com/](http://p.yusukekamiyamane.com/) ([https://github.com/yusukekamiyamane/fugue-icons/tree/master/icons-shadowless](https://github.com/yusukekamiyamane/fugue-icons/tree/master/icons-shadowless))

Favicon from [https://github.com/antirez/redis-io/blob/master/public/images/favicon.png](https://github.com/antirez/redis-io/blob/master/public/images/favicon.png)
