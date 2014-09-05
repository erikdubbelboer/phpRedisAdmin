<?php


if (!isset($_POST['post'])) {
  die('Javascript needs to be enabled for you to flush a database.');
}


require_once 'includes/common.inc.php';

if ($server['debug'] == true) {
    $redis->flushdb();
else {
    die ("Forbidden operation!");
}


