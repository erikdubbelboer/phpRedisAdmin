<?php

function nonce_store_path() {
  return sys_get_temp_dir() . '/phpredisadmin_nonces_' . hash('sha256', PHPREDIS_ADMIN_PATH) . '.json';
}

function nonce_store_open() {
  $fh = fopen(nonce_store_path(), 'c+');
  if ($fh === false || !flock($fh, LOCK_EX)) {
    throw new RuntimeException('Unable to open the Digest nonce store.');
  }

  @chmod(nonce_store_path(), 0600);
  return $fh;
}

function nonce_store_read($fh) {
  rewind($fh);
  $contents = stream_get_contents($fh);
  $store = json_decode($contents === false || $contents === '' ? '{}' : $contents, true);
  return is_array($store) ? $store : array();
}

function nonce_store_prune($store) {
  $minimum = time() - 300;

  foreach ($store as $nonce => $state) {
    if (!is_array($state) || !isset($state['issued']) || $state['issued'] <= $minimum) {
      unset($store[$nonce]);
    }
  }

  if (count($store) > 1024) {
    uasort($store, function($left, $right) {
      return $left['issued'] <=> $right['issued'];
    });
    $store = array_slice($store, -1024, null, true);
  }

  return $store;
}

function nonce_store_write($fh, $store) {
  $encoded = json_encode($store);
  if ($encoded === false) {
    throw new RuntimeException('Unable to encode the Digest nonce store.');
  }

  rewind($fh);
  if (!ftruncate($fh, 0) || fwrite($fh, $encoded) === false || !fflush($fh)) {
    throw new RuntimeException('Unable to update the Digest nonce store.');
  }
}

function nonce_store_close($fh) {
  flock($fh, LOCK_UN);
  fclose($fh);
}

function nonce_issue() {
  $nonce = bin2hex(random_bytes(32));
  $fh = nonce_store_open();

  try {
    $store = nonce_store_prune(nonce_store_read($fh));
    $store[$nonce] = array('issued' => time(), 'counts' => array());
    $store = nonce_store_prune($store);
    nonce_store_write($fh, $store);
  } finally {
    nonce_store_close($fh);
  }

  return $nonce;
}

function nonce_commit_count($nonce, $username, $cnonce, $nc) {
  if (!is_string($nc) || strlen($nc) !== 8 || !ctype_xdigit($nc)) {
    return false;
  }

  $count = hexdec($nc);
  if ($count < 1) {
    return false;
  }

  $fh = nonce_store_open();

  try {
    $store = nonce_store_prune(nonce_store_read($fh));
    if (!isset($store[$nonce])) {
      nonce_store_write($fh, $store);
      return false;
    }

    $client = hash('sha256', $username . "\0" . $cnonce);
    $previous = isset($store[$nonce]['counts'][$client]) ? $store[$nonce]['counts'][$client] : 0;
    if ($count <= $previous) {
      return false;
    }

    $store[$nonce]['counts'][$client] = $count;
    nonce_store_write($fh, $store);
    return true;
  } finally {
    nonce_store_close($fh);
  }
}

function digest_reject($realm, $opaque, $message = '') {
  http_response_code(401);
  header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",algorithm=SHA-256,nonce="'.nonce_issue().'",opaque="'.$opaque.'"');
  die($message);
}

// This fill will perform HTTP digest authentication. This is not the most secure form of authentication so be carefull when using this.
function authHttpDigest()
{
    global $config;

    $realm = 'phpRedisAdmin';

    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $opaque = hash('sha256', 'phpRedisAdmin'.$userAgent.$remoteAddress);


    if (!isset($_SERVER['PHP_AUTH_DIGEST']) || empty($_SERVER['PHP_AUTH_DIGEST'])) {
      digest_reject($realm, $opaque);
    }

    $needed_parts = array(
      'nonce'    => 1,
      'nc'       => 1,
      'cnonce'   => 1,
      'qop'      => 1,
      'username' => 1,
      'uri'      => 1,
      'response' => 1,
      'opaque'   => 1
     );

    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('/('.$keys.')=(?:([\'"])([^\2]+?)\2|([^\s,]+))/', $_SERVER['PHP_AUTH_DIGEST'], $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
      $data[$m[1]] = $m[3] ? $m[3] : $m[4];
      unset($needed_parts[$m[1]]);
    }

    if (!empty($needed_parts)) {
      digest_reject($realm, $opaque);
    }

    if ($data['qop'] !== 'auth' || !hash_equals($opaque, $data['opaque']) || !hash_equals($_SERVER['REQUEST_URI'], $data['uri'])) {
      digest_reject($realm, $opaque);
    }

    if (!isset($config['login'][$data['username']])) {
      digest_reject($realm, $opaque, 'Invalid username and/or password combination.');
    }

    $login         = $config['login'][$data['username']];
    $login['name'] = $data['username'];

    $password = hash('sha256', $login['name'].':'.$realm.':'.$login['password']);
    $request = hash('sha256', $_SERVER['REQUEST_METHOD'].':'.$data['uri']);
    $response = hash('sha256', $password.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$request);

    if (!hash_equals($response, strtolower($data['response']))) {
      digest_reject($realm, $opaque, 'Invalid username and/or password combination.');
    }

    if (!nonce_commit_count($data['nonce'], $data['username'], $data['cnonce'], $data['nc'])) {
      digest_reject($realm, $opaque);
    }

    return $login;
}

// Perform auth using a standard HTML <form> submission and cookies to save login state
function authCookie()
{
    global $config;

    $generateCookieHash = function($username) use ($config) {
        if (!isset($config['login'][$username])) {
            throw new \RuntimeException("Invalid username");
        }

        // Storing this value client-side so we need to be careful that it
        //  doesn't reveal anything nor can be guessed.
        // Using SHA512 because MD5, SHA1 are both now considered broken
        return hash(
            'sha512',
            implode(':', array(
                $username,
                $_SERVER['HTTP_USER_AGENT'],
                $_SERVER['REMOTE_ADDR'],
                $config['login'][$username]['password'],
            ))
        );
    };

    if (!empty($_COOKIE['phpRedisAdminLogin'])) {
        // We have a cookie; is it correct?
        // Cookie value looks like "username:password-hash"
        $cookieVal = explode(':', $_COOKIE['phpRedisAdminLogin']);
        if (count($cookieVal) === 2) {
            list($username, $cookieHash) = $cookieVal;
            if (isset($config['login'][$username])) {
                $userData = $config['login'][$username];
                $expectedHash = $generateCookieHash($username);

                if ($cookieHash === $expectedHash) {
                    // Correct username & password
                    return $userData;
                }
            }
        }
    }

    if (isset($_POST['username'], $_POST['password'])) {
        // Login form submitted; correctly?
        if (isset($config['login'][$_POST['username']])) {
            $userData = $config['login'][$_POST['username']];
            if ($_POST['password'] === $userData['password']) {
                // Correct username & password. Set cookie and redirect to home page
                $cookieValue = $_POST['username'] . ':' . $generateCookieHash($_POST['username']);
                setcookie('phpRedisAdminLogin', $cookieValue);

                // This should be an absolute URL, but that's a bit of a pain to generate; this will work
                header("Location: index.php");
                die();
            }
        }
    }

    // If we're here, we don't have a valid login cookie and we don't have a
    //  valid form submission, so redirect to the login page if we aren't
    //  already on that page
    if (!defined('LOGIN_PAGE')) {
        header("Location: login.php");
        die();
    }

    // We must be on the login page without a valid cookie or submission
    return null;
}

if (!empty($config['cookie_auth'])) {
    $login = authCookie();
} else {
    $login = authHttpDigest();
}

?>
