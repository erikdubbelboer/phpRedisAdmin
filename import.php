<?php

require_once 'common.inc.php';




// This mess could need some cleanup!
if (isset($_POST['commands'])) {
  $commands = explode("\n", $_POST['commands']);

  foreach ($commands as $command) {
    $command = str_getcsv($command, ' ');

    // Is it an empty line?
    if (is_null($command[0])) {
      continue;
    }

    // Do we have enough arguments?
    if (count($command) < 3) {
      continue;
    }

    // Some commands need 3 arguments, make sure we always have a 3e argument.
    if (!isset($command[3])) {
      $command[3] = '';
    }

    $command[0] = strtoupper($command[0]);

    switch ($command[0]) {
      case 'SET': {
        $redis->set($command[1], $command[2]);
        break;
      }

      case 'HSET': {
        $redis->hSet($command[1], $command[2], $command[3]);
        break;
      }
      
      case 'LPUSH': {
        $redis->lPush($command[1], $command[2]);
        break;
      }

      case 'RPUSH': {
        $redis->rPush($command[1], $command[2]);
        break;
      }

      case 'LSET': {
        $redis->lSet($command[1], $command[2], $command[3]);
        break;
      }

      case 'SADD': {
        $redis->sAdd($command[1], $command[2]);
        break;
      }

      case 'ZADD': {
        $redis->zAdd($command[1], $command[2], $command[3]);
        break;
      }
    }
  }


  // Refresh the top so the key tree is updated.
  require 'header.inc.php';

  ?>
  <script>
  top.location.href = top.location.pathname+'?overview&s=<?php echo $server['id']?>';
  </script>
  <?php

  require 'footer.inc.php';
  die;
}




$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2>Import</h2>
<form action="<?php echo format_html($_SERVER['REQUEST_URI'])?>" method="post">

<p>
<label for="commands">Commands:<br>
<br>
<span class="info">
Valid are:<br>
SET<br>
HSET<br>
LPUSH<br>
RPUSH<br>
LSET<br>
SADD<br>
ZADD
</span>
</label>
<textarea name="commands" id="commands" cols="80" rows="20"></textarea>
</p>

<p>
<input type="submit" class="button" value="Import">
</p>

</form>
<?php

require 'footer.inc.php';

?>
