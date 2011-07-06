<?

require 'config.inc.php';



if (isset($config['login'])) {
  require 'login.inc.php';
}


$redis = new Redis();
if (!$redis->connect($config['host'], $config['port'])) {
  die('ERROR: Could not connect to Redis');
}

if (isset($config['auth'])) {
  if (!$redis->auth($config['auth'])) {
    die('ERROR: Authentication failed.');
  }
}


$page = array(
  'css' => array('common'),
  'js'  => array()
);



$types = array(
  Redis::REDIS_STRING    => 'string',
  Redis::REDIS_SET       => 'set',
  Redis::REDIS_LIST      => 'list',
  Redis::REDIS_ZSET      => 'zset',
  Redis::REDIS_HASH      => 'hash',
  Redis::REDIS_NOT_FOUND => 'other'
);



// Undo magic quotes
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




function is_ie() {
  if (isset($_SERVER['HTTP_USER_AGENT']) &&
      (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    return true;
  } else {
    return false;
  }
}


function format_html($str) {
  return htmlentities($str, ENT_COMPAT, 'UTF-8');
}


function format_ago($time, $ago = false) {
  $minute = 60;
  $hour   = $minute * 60;
  $day    = $hour   * 24;

  $when = $time;

  if ($when >= 0)
    $suffix = 'ago';
  else {
    $when = -$when;
    $suffix = 'in the future';
  }

  if ($when > $day) {
    $when = round($when / $day);
    $what = 'day';
  } else if ($when > $hour) {
    $when = round($when / $hour);
    $what = 'hour';
  } else if ($when > $minute) {
    $when = round($when / $minute);
    $what = 'minute';
  } else {
    $what = 'second';
  }

  if ($when != 1) $what .= 's';

  if ($ago) {
    return "$when $what $suffix";
  } else {
    return "$when $what";
  }
}


function format_size($size) {
  $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

  if ($size == 0) {
    return '0 B';
  } else {
    return round($size / pow(1024, ($i = floor(log($size, 1024)))), 1).' '.$sizes[$i];
  }
}


function str_rand($length) {
  $r = '';

  for (; $length > 0; --$length) {
    $r .= chr(rand(32, 126));
  }

  return $r;
}

