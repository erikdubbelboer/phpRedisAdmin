<?php

$config = array(
  'servers' => array(
    array(
      'name' => 'localhost', // Optional name.
      'host' => '127.0.0.1',
      'port' => 6379,

      // Optional authentication.
      //'auth' => 'redispasswordhere' // Warning: The password is send in plain-text to the redis server.
    ),

    /*array(
      'host' => 'localhost',
      'port' => 6380
    )*/
  ),


  'seperator' => ':',


  // Uncomment to show less information and make phpRedisAdmin fire less commands to the Redis server. Recommended for a really busy Redis server.
  //'faster' => true,


  // Uncomment to enable HTTP authentication
  /*'login' => array(
    // Username => Password
    // Multiple combinations can be used
    'username' => 'password'
  ),*/




  // You can ignore settings below this point.

  'maxkeylen' => 100
);

?>