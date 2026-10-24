<?php

if (!isset($_POST['post'])) {
  die('Javascript needs to be enabled for you to flush a database.');
}

require_once 'includes/common.inc.php';
global $redis, $config, $csrfToken, $server;

if (!server_can_flush($server)) {
  http_response_code(403);
  die('Flushing is disabled or the server has a key filter.');
}

$redis->flushdb();

