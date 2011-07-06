<?

require 'common.inc.php';




function export_redis($key) {
  global $types, $redis;

  $type = $redis->type($key);
  $type = $types[$type];
  
  if ($type == 'string') {
    echo 'SET ',$key,' "',addslashes($redis->get($key)),'"',PHP_EOL;
  } else if ($type == 'hash') {
    $values = $redis->hGetAll($key);

    foreach ($values as $k => $v) {
      echo 'HSET ',$key,' ',$k,' "',addslashes($v),'"',PHP_EOL;
    }
  } else if ($type == 'list') {
    $size = $redis->lSize($key);

    for ($i = 0; $i < $size; ++$i) {
      echo 'LPUSH ',$key,' "',addslashes($redis->lGet($key, $i)),'"',PHP_EOL;
    }
  } else if ($type == 'set') {
    $values = $redis->sMembers($key);

    foreach ($values as $v) {
      echo 'SADD ',$key,' "',addslashes($v),'"',PHP_EOL;
    }
  } else if ($type == 'zset') {
    $values = $redis->zRange($key, 0, -1);

    foreach ($values as $v) {
      $s = $redis->zScore($key, $v);

      echo 'ZADD ',$key,' ',$s,' "',addslashes($v),'"',PHP_EOL;
    }
  }
}


function export_json($key) {
  global $types, $redis;

  $type = $redis->type($key);
  $type = $types[$type];
  
  if ($type == 'string') {
    $value = $redis->get($key);
  } else if ($type == 'hash') {
    $value = $redis->hGetAll($key);
  } else if ($type == 'list') {
    $size  = $redis->lSize($key);
    $value = array();

    for ($i = 0; $i < $size; ++$i) {
      $value[] = $redis->lGet($key, $i);
    }
  } else if ($type == 'set') {
    $value = $redis->sMembers($key);
  } else if ($type == 'zset') {
    $value = $redis->zRange($key, 0, -1);
  }

  if (isset($value)) {
    return $value;
  } else {
    return undefined;
  }
}




if (isset($_POST['type'])) {
  if ($_POST['type'] == 'json') {
    $ext = 'js';
    $ct  = 'application/json';
  } else {
    $ext = 'redis';
    $ct  = 'text/plain';
  }


  header('Content-type: '.$ct.'; charset=utf-8');
  header('Content-Disposition: inline; filename="export.'.$ext.'"');
    
  
  if ($_POST['type'] == 'json') {
    if (isset($_GET['key'])) {
      echo json_encode(export_json($_GET['key']));
    } else {
      $keys = $redis->keys('*');
      $vals = array();

      foreach ($keys as $key) {
        $vals[$key] = export_json($key);
      }

      echo json_encode($vals);
    }
  } else {
    if (isset($_GET['key'])) {
      export_redis($_GET['key']);
    } else {
      $keys = $redis->keys('*');

      foreach ($keys as $key) {
        export_redis($key);
      }
    }
  }

  die;
}




$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2>Export <?=isset($_GET['key']) ? format_html($_GET['key']) : ''?></h2>

<form action="<?=format_html($_SERVER['REQUEST_URI'])?>" method="post">

<p>
<label for="type">Type:</label>
<select name="type" id="type">
<option value="redis" <?=(isset($_GET['type']) && ($_GET['type'] == 'redis')) ? 'selected="selected"' : ''?>>Redis</option>
<option value="json"  <?=(isset($_GET['type']) && ($_GET['type'] == 'json' )) ? 'selected="selected"' : ''?>>JSON</option>
</select>
</p>

<p>
<input type="submit" class="button" value="Export">
</p>

</form>
<?

require 'footer.inc.php';

