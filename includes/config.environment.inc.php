<?php

include 'config.sample.inc.php';

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

while (TRUE) {

  $prefix = 'REDIS_' . $i . '_';

  $server_name = getenv($prefix . 'NAME');
  $server_host = getenv($prefix . 'HOST');
  $server_port = getenv($prefix . 'PORT');

  if (empty($server_host)) {
    break;
  }

  if (empty($server_name)) {
    $server_name = $server_host;
  }

  if (empty($server_port)) {
    $server_port = 6379;
  }

  $config['servers'][] = array(
      'name'   => $server_name,
      'host'   => $server_host,
      'port'   => $server_port,
      'filter' => '*',
  );

  $i++;
}
