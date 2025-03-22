<?php
require __DIR__ . '/../vendor/autoload.php';

define('PHPREDIS_ADMIN_PATH', dirname(__DIR__));

if (session_status() !== PHP_SESSION_DISABLED) {
  session_start();

  if (isset($_SESSION['phpredisadmin_csrf'])) {
    $csrfToken = $_SESSION['phpredisadmin_csrf'];
  } else {
    $csrfToken = bin2hex(random_bytes(16));
    $_SESSION['phpredisadmin_csrf'] = $csrfToken;
  }
} else {
  $csrfToken = 'nosession';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($_POST['csrf'] !== $csrfToken) {
    die('bad csrf token');
  }
}


// These includes are needed by each script.
if(file_exists(PHPREDIS_ADMIN_PATH . '/includes/config.inc.php')){
  require_once PHPREDIS_ADMIN_PATH . '/includes/config.inc.php';
}else{
  require_once PHPREDIS_ADMIN_PATH . '/includes/config.sample.inc.php';
}
require_once PHPREDIS_ADMIN_PATH . '/includes/functions.inc.php';
require_once PHPREDIS_ADMIN_PATH . '/includes/page.inc.php';

if (isset($config['login'])) {
  require_once PHPREDIS_ADMIN_PATH . '/includes/login.inc.php';
}


if (isset($login['servers'])) {
  $i = current($login['servers']);
} else {
  $i = 0;
}

if (isset($_GET['s']) && is_numeric($_GET['s']) && ($_GET['s'] < count($config['servers']))) {
  $i = $_GET['s'];
}

$server            = $config['servers'][$i];
$server['id']      = $i;
$server['charset'] = isset($server['charset']) && $server['charset'] ? $server['charset'] : false;

mb_internal_encoding('utf-8');

if (isset($login, $login['servers'])) {
  if (array_search($i, $login['servers']) === false) {
    die('You are not allowed to access this database.');
  }

  foreach ($config['servers'] as $key => $ignore) {
    if (array_search($key, $login['servers']) === false) {
      unset($config['servers'][$key]);
    }
  }
}


if (!isset($server['db'])) {
  if (isset($_GET['d']) && is_numeric($_GET['d'])) {
    $server['db'] = $_GET['d'];
  } else {
    $server['db'] = 0;
  }
}


if (!isset($server['filter'])) {
  $server['filter'] = '*';
}

// filter from GET param
if (isset($_GET['filter']) && $_GET['filter'] != '') {
    $server['filter'] = $_GET['filter'];
    if (strpos($server['filter'], '*') === false) {
      $server['filter'].= '*';
    }
}

if (!isset($server['seperator'])) {
  $server['seperator'] = $config['seperator'];
}

if (!isset($server['keys'])) {
  $server['keys'] = $config['keys'];
}

if (!isset($server['scansize'])) {
  if (isset($config['scansize'])) {
    $server['scansize'] = $config['scansize'];
  } else {
    $server['scansize'] = 1000;
  }
}

if (!isset($server['scanmax'])) {
  if (isset($config['scanmax'])) {
    $server['scanmax'] = $config['scanmax'];
  } else {
    $server['scanmax'] = 0;
  }
}

if (!isset($server['serialization'])) {
  if (isset($config['serialization'])) {
    $server['serialization'] = $config['serialization'];
  }
}

if (!isset($config['hideEmptyDBs'])) {
  $config['hideEmptyDBs'] = false;
}

if (!isset($config['showEmptyNamespaceAsKey'])) {
  $config['showEmptyNamespaceAsKey'] = false;
}

if (!isset($server['scheme']) || empty($server['scheme'])) {
  $server['scheme'] = 'tcp';
}

// Setup a connection to Redis.
if ($server['scheme'] === 'unix' && $server['path']) {
  $redis = new Predis\Client(array('scheme' => 'unix', 'path' => $server['path']));
} else {
  $redis = !$server['port'] ? new Predis\Client($server['host']) : new Predis\Client($server['scheme'].'://'.$server['host'].':'.$server['port']);
}

try {
    $redis->connect();
} catch (Predis\CommunicationException $exception) {
    die('ERROR: ' . $exception->getMessage());
}

if (isset($server['auth'])) {
  if (!$redis->auth($server['auth'])) {
    die('ERROR: Authentication failed ('.$server['host'].':'.$server['port'].')');
  }
}

if (!isset($config['login']) && !empty($config['login_as_acl_auth'])) {
  require_once PHPREDIS_ADMIN_PATH . '/includes/login_acl.inc.php';
}

if ($server['db'] != 0) {
  if (!$redis->select($server['db'])) {
    die('ERROR: Selecting database failed ('.$server['host'].':'.$server['port'].','.$server['db'].')');
  }
}
