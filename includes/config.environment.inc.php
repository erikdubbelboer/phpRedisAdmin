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
$config['seperator']               = getenv('SEPERATOR')                   ?: ':';
$config['showEmptyNamespaceAsKey'] = getenv('SHOW_EMPTY_NAMESPACE_AS_KEY') ?: false;

$admin_user = getenv('ADMIN_USER');
$admin_pass = getenv('ADMIN_PASS');

if (!empty($admin_user)) {
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

  if (empty($server_port)) {
    $server_port = 6379;
  }

  $config['servers'][] = array(
      'name'     => $server_name,
      'host'     => $server_host,
      'port'     => $server_port,
      'filter'   => $config['filter'],
      'scansize' => $config['scansize'],
  );

  if (!empty($server_auth)) {
    $config['servers'][$i-1]['auth'] = $server_auth;
  }

  if (!empty($server_databases)) {
    $config['servers'][$i-1]['databases'] = $server_databases;
  }

  $i++;
}
