<?php 

if (!class_exists('Redis')) {
  die('ERROR: phpredis is required. You can find phpredis at <a href="https://github.com/nicolasff/phpredis">https://github.com/nicolasff/phpredis</a>');
}




// Undo magic quotes (both in keys and values)
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
  $process = array(&$_GET, &$_POST);
  
  while (list($key, $val) = each($process)) {
    foreach ($val as $k => $v) {
      unset($process[$key][$k]);

      if (is_array($v)) {
        $process[$key][stripslashes($k)] = $v;
        $process[] = &$process[$key][stripslashes($k)];
      } else {
        $process[$key][stripslashes($k)] = stripslashes($v);
      }
    }
  }

  unset($process);
}




// These includes are needed by each script.
require_once 'config.inc.php';
require_once 'functions.inc.php';
require_once 'page.inc.php';


if (isset($config['login'])) {
  require_once 'login.inc.php';
}




// phpredis types to string conversion array.
$redistypes = array(
  Redis::REDIS_STRING    => 'string',
  Redis::REDIS_SET       => 'set',
  Redis::REDIS_LIST      => 'list',
  Redis::REDIS_ZSET      => 'zset',
  Redis::REDIS_HASH      => 'hash',
);





$i = 0;

if (isset($_GET['s']) && is_numeric($_GET['s']) && ($_GET['s'] < count($config['servers']))) {
  $i = $_GET['s'];
}

$server       = $config['servers'][$i];
$server['id'] = $i;


if (!isset($server['db'])) {
  $server['db'] = 0;
}


// Setup a connection to Redis.
$redis = new Redis();

try {
  $redis->connect($server['host'], $server['port']);
} catch (Exception $e) {
  die('ERROR: Could not connect to Redis ('.$server['host'].':'.$server['port'].')');
}


if (isset($server['auth'])) {
  if (!$redis->auth($server['auth'])) {
    die('ERROR: Authentication failed ('.$server['host'].':'.$server['port'].')');
  }
}


if ($server['db'] != 0) {
  if (!$redis->select($server['db'])) {
    die('ERROR: Selecting database failed ('.$server['host'].':'.$server['port'].','.$server['db'].')');
  }
}

?>
