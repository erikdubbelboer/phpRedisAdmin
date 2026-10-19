<?php

include 'config.sample.inc.php';

// get configs from environment variables
$config['cookie_auth']             = getenv('COOKIE_AUTH')                 ?: false;
$config['count_elements_page']     = getenv('COUNT_ELEMENTS_PAGE')         ?: 100;
$config['faster']                  = getenv('FASTER')                      ?: true;
$config['filter']                  = getenv('FILTER')                      ?: '*';
$config['hideEmptyDBs']            = getenv('HIDE_EMPTY_DBS')              ?: false;
$config['keys']                    = getenv('KEYS')                        ?: false;
$config['maxkeylen']               = getenv('MAX_KEY_LEN')                 ?: 100;
$config['scansize']                = getenv('SCAN_SIZE')                   ?: 1000;
$config['scanmax']                 = getenv('SCAN_MAX')                    ?: 1000;
$config['seperator']               = getenv('SEPERATOR')                   ?: ':';
$config['showEmptyNamespaceAsKey'] = getenv('SHOW_EMPTY_NAMESPACE_AS_KEY') ?: false;

$admin_user = getenv('ADMIN_USER');
$admin_pass = getenv('ADMIN_PASS');
$admin_user_set = $admin_user !== false && $admin_user !== '';
$admin_pass_set = $admin_pass !== false && $admin_pass !== '';
$allow_no_auth = getenv('ALLOW_NO_AUTH') === 'true';

// Partial credentials are almost always a deployment mistake. Fail closed even
// when the explicit no-auth development mode is enabled.
if ($admin_user_set !== $admin_pass_set) {
  header('HTTP/1.1 403 Forbidden');
  die('ADMIN_USER and ADMIN_PASS must both be set.');
}

if (!$allow_no_auth && !$admin_user_set) {
  header('HTTP/1.1 403 Forbidden');
  die('Authentication is required. Set ADMIN_USER and ADMIN_PASS, or set ALLOW_NO_AUTH=true for an isolated development instance.');
}

$config['allow_no_auth'] = $allow_no_auth;

if ($admin_user_set) {
  $config['login'] = array(
    $admin_user => array(
      'password' => $admin_pass,
    ),
  );
}

$i=1;
$config['servers'] = array();

while (true) {

  $prefix = 'REDIS_' . $i . '_';

  $server_name = getenv($prefix . 'NAME');
  $server_host = getenv($prefix . 'HOST');
  $server_port = getenv($prefix . 'PORT');
  $server_scheme = getenv($prefix . 'SCHEME');
  if (getenv($prefix . 'AUTH_FILE') !== false) {
    $server_auth = file_get_contents(getenv($prefix . 'AUTH_FILE'));
  } else {
    $server_auth = getenv($prefix . 'AUTH');
  }
  $server_databases = getenv($prefix . 'DATABASES');

  if (empty($server_host)) {
    break;
  }

  if (empty($server_name)) {
    $server_name = $server_host;
  }

  if (empty($server_auth)) {
    $server_auth = "";
  }

  if (empty($server_port) && strpos($server_host, ':') === false) {
    $server_port = 6379;
  }

  if (empty($server_scheme)) {
    $server_scheme = 'tcp';
  }

  $config['servers'][] = array(
      'name'     => $server_name,
      'host'     => $server_host,
      'port'     => $server_port,
      'filter'   => $config['filter'],
      'scansize' => $config['scansize'],
      'scanmax'  => $config['scanmax'],
      'scheme'   => $server_scheme,
  );

  if (!empty($server_auth)) {
    $config['servers'][$i-1]['auth'] = $server_auth;
  }

  if (!empty($server_databases)) {
    $config['servers'][$i-1]['databases'] = $server_databases;
  }

  $i++;
}
