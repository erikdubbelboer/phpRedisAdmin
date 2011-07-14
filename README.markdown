phpRedisAdmin
=============

phpRedisAdmin is a simple web interface to manage [Redis](http://redis.io/) databases. It is released under the [Creative Commons Attribution 3.0 license](http://creativecommons.org/licenses/by/3.0/). This code is being developed and maintained by [Erik Dubbelboer](https://github.com/ErikDubbelboer/).

You can send comments, patches, questions [here on github](https://github.com/ErikDubbelboer/phpRedisAdmin/issues) or to erik@dubbelboer.com.


Example
=======

You can find an example database at [http://dubbelboer.com/phpRedisAdmin/](http://dubbelboer.com/phpRedisAdmin/?view&key=example:hash)


Installing/Configuring
======================

You will need [phpredis](https://github.com/nicolasff/phpredis). See phpredis for install instructions.

You will need to edit config.inc.php with your redis information. You might also want to uncomment and change the login information in config.inc.php.


TODO
====

* Javascript sorting of tables
* Make delete a POST request
* Better error handling
* Move or Copy key to different server
* Importing of databases (json and redis commands)
* JSON export with seperate objects based on your seperator


Credits
=======

Icons by [http://p.yusukekamiyamane.com/](http://p.yusukekamiyamane.com/) ([https://github.com/yusukekamiyamane/fugue-icons/tree/master/icons-shadowless](https://github.com/yusukekamiyamane/fugue-icons/tree/master/icons-shadowless))

