<?php

require_once 'common.inc.php';




$info = array();

foreach ($config['servers'] as $i => $server) {
  if (!isset($server['db'])) {
      $server['db'] = 0;
  }

  // Setup a connection to this Redis server.
  $redis->close();

  try {
    $redis->connect($server['host'], $server['port']);
  } catch (Exception $e) {
    die('ERROR: Could not connect to Redis ('.$server['host'].':'.$server['port'].')');
  }


  if (isset($server['auth'])) {
    if (!$redis->auth($server['auth'])) {
      die('ERROR: Authentication failed ('.$server['host'].':'.$server['port'].')');
    }
  }


  if ($server['db'] != 0) {
    if (!$redis->select($server['db'])) {
      die('ERROR: Selecting database failed ('.$server['host'].':'.$server['port'].','.$server['db'].')');
    }
  }


  $info[$i]         = $redis->info();
  $info[$i]['size'] = $redis->dbSize();
}




$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>

<?php foreach ($config['servers'] as $i => $server) { ?>
  <div class="server">
  <h2><?php echo isset($server['name']) ? $server['name'] : format_html($server['host'])?></h2>

  <table>

  <tr><td><div>Redis version:</div></td><td><div><?php echo $info[$i]['redis_version']?></div></td></tr>

  <tr><td><div>Keys:</div></td><td><div><?php echo $info[$i]['size']?></div></td></tr>

  <tr><td><div>Memory used:</div></td><td><div><?php echo format_size($info[$i]['used_memory'])?></div></td></tr>

  <tr><td><div>Uptime:</div></td><td><div><?php echo format_ago($info[$i]['uptime_in_seconds'])?></div></td></tr>

  <tr><td><div>Last save:</div></td><td><div><?php echo format_ago(time() - $info[$i]['last_save_time'], true)?> <a href="save.php?s=<?php echo $i?>"><img src="images/save.png" width="16" height="16" title="Save Now" alt="[S]" class="imgbut"></a></div></td></tr>

  </table>
  </div>
<?php } ?>

<p class="clear">
<a href="https://github.com/ErikDubbelboer/phpRedisAdmin" target="_blank">phpRedisAdmin on GitHub</a>
</p>

<p>
<a href="http://redis.io/documentation" target="_blank">Redis Documentation</a>
</p>
<?php

require 'footer.inc.php';

?>
