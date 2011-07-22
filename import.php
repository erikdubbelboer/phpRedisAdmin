<?php

require_once 'common.inc.php';




if (isset($_POST['commands'])) {
  $commands = explode("\n", $_POST['commands']);

  foreach ($commands as $command) {
    // The command and it's arguments are always seperated by a space
    $command = explode(' ', trim($command), 2);

    if (!isset($command[1])) {
      // It's just an empty line or simple command which we don't support.
      continue;
    }

    // Is the key enclosed in ""?
    if ($command[1][0] == '"') {
      // We can't just explode since we might have \" inside the key. So do a preg_split on " with a negative lookbehind assertions to make sure it isn't a \".
      $key = preg_split('/(?<!\\\\)"/', substr($command[1], 1), 2);

      // Strip the seperating space
      $key[1] = substr($key[1], 1);
    } else {
      $key = explode(' ', $command[1], 2);
    }

    switch ($command[0]) {
      case 'SET': {
        // Trim the optional "" acount th value.
        $redis->set($key[0], trim($key[1], '"'));
        break;
      }

      case 'HSET': {
        if ($key[1][0] == '"') {
          // See preg_split above.
          $hkey = preg_split('/(?<!\\\\)"/', substr($key[1], 1), 2);
      
          // Strip the seperating space
          $hkey[1] = substr($hkey[1], 1);
        } else {
          $hkey = explode(' ', $key[1], 2);
        }

        $redis->hSet($key[0], $hkey[0], trim($hkey[1], '"'));
        break;
      }

      case 'RPUSH': {
        $redis->rPush($key[0], trim($key[1], '"'));
        break;
      }

      case 'SADD': {
        $redis->sAdd($key[0], trim($key[1], '"'));
        break;
      }

      case 'ZADD': {
        if ($key[1][0] == '"') {
          // See preg_split ebove.
          $score = preg_split('/(?<!\\\\)"/', substr($key[1], 1), 2);
      
          // Strip the seperating space
          $score[1] = substr($score[1], 1);
        } else {
          $score = explode(' ', $key[1], 2);
        }

        $redis->zAdd($key[0], $score[0], trim($score[1], '"'));
        break;
      }

      // We ignore commands we don't know (Could produce a warning).
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
RPUSH<br>
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
