<?php

include 'config.sample.inc.php';

$config['scanmax']                = getenv('SCAN_MAX')                    ?: 1000;

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
      'name'   => $server_name,
      'host'   => $server_host,
      'port'   => $server_port,
      'filter' => '*',
      'scanmax'  => $config['scanmax'],
  );
  
  if (!empty($server_auth)) {
    $config['servers'][$i-1]['auth'] = $server_auth;
  } 
  
  if (!empty($server_databases)) {
    $config['servers'][$i-1]['databases'] = $server_databases;
  } 

  $i++;
}
