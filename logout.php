<?php

define('PHPREDIS_ADMIN_PATH', __DIR__);

if (file_exists(PHPREDIS_ADMIN_PATH . '/includes/config.inc.php')) {
  require_once PHPREDIS_ADMIN_PATH . '/includes/config.inc.php';
} else {
  require_once PHPREDIS_ADMIN_PATH . '/includes/config.sample.inc.php';
}

if (!empty($config['cookie_auth'])) {
  // Cookie-based auth
  session_start();
  unset($_SESSION['phpRedisAdminLogin']);
  header("Location: login.php");
  die();
} else {
  header('HTTP/1.1 401 Unauthorized');
  die('<html><head><meta http-equiv="refresh" content="0; url=/index.php" /></head></html>');
}
