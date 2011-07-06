<?

$realm = md5('phpRedisAdmin'.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);

if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
  header('HTTP/1.1 401 Unauthorized');
  header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
  die;
}

$needed_parts = array(
  'nonce'    => 1,
  'nc'       => 1,
  'cnonce'   => 1,
  'qop'      => 1,
  'username' => 1,
  'uri'      => 1,
  'response' => 1
 );

$data = array();
$keys = implode('|', array_keys($needed_parts));

preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $_SERVER['PHP_AUTH_DIGEST'], $matches, PREG_SET_ORDER);

foreach ($matches as $m) {
  $data[$m[1]] = $m[3] ? $m[3] : $m[4];
  unset($needed_parts[$m[1]]);
}

if (!empty($needed_parts)) {
  die('error=1');
}

if (!isset($config['login'][$data['username']])) {
  header('HTTP/1.1 401 Unauthorized');
  header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
  die('Invalid username and/or password combination.');
}

$password = md5($data['username'].':'.$realm.':'.$config['login'][$data['username']]);

$response = md5($password.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']));

if ($data['response'] != $response) {
  header('HTTP/1.1 401 Unauthorized');
  header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
  die('Invalid username and/or password combination.');
}

