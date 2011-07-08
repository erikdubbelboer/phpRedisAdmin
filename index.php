<?

require 'common.inc.php';




$page['css'][] = 'index';
$page['js'][]  = 'index';

require 'header.inc.php';


?>
<div id="sidebar">

<h1 class="logo"><a href="?">phpRedisAdmin</a></h1>

<p>
<a href="?info"><img src="images/info.png" width="16" height="16" title="Info" alt="[I]"></a>
<a href="?export"><img src="images/export.png" width="16" height="16" title="Export" alt="[E]"></a>
</p>

<p>
<a href="?edit" class="add">Add another key</a>
</p>

<div id="keys">
<?


$keys = $redis->keys('*');

sort($keys);

$dirs = array();

foreach ($keys as $key) {
  $key = explode($config['seperator'], $key);

  $a = &$dirs;

  for ($i = 0; $i < (count($key) - 1); ++$i) {
    if (!isset($a[$key[$i]])) {
      $a[$key[$i]] = array();
    }

    $a = &$a[$key[$i]];
  }

  $a[$key[count($key) - 1]] = true;
  unset($a);
}


function print_tree($item, $key, $all, $last) {
  global $config, $types, $redis;

  if ($item === true) {
    $type = $redis->type($all);

    if (!isset($types[$type])) {
      return;
    }

    $type = $types[$type];

    $class = array();

    if (isset($_GET['key']) && ($all == $_GET['key'])) {
      $class[] = 'current';
    }
    if ($last) {
      $class[] = 'last';
    }

    ?>
    <li<?=empty($class) ? '' : ' class="'.implode(' ', $class).'"'?>>
    <a href="?view&amp;key=<?=format_html($all)?>"><?=format_html($key)?><?

    $len = false;

    if ($type == 'hash') {
      $len = $redis->hLen($all);
    } else if ($type == 'list') {
      $len = $redis->lSize($all);
    } else if ($type == 'set') {
      $len = count($redis->sMembers($all));
    } else if ($type == 'zset') {
      $len = count($redis->zRange($all, 0, -1));
    }

    if ($len !== false) {
      ?> <span class="info">(<?=$len?>)</span><?
    }
      
    ?>
    </a>
    </li>
    <?
  } else {
    ?>
    <li class="folder<?=empty($all) ? '' : ' collapsed'?><?=$last ? ' last' : ''?>"><div class="icon"><?=format_html($key)?></div><ul>
    <?

    $l = count($item);

    foreach ($item as $k => $v) {
      if (empty($all)) {
        $a = $k;
      } else {
        $a = $all.$config['seperator'].$k;
      }

      print_tree($v, $k, $a, ($l == 1));

      --$l;
    }

    ?>
    </ul>
    </li>
    <?
  }
}

?>
<ul>
<?print_tree($dirs, 'Keys', '', empty($dirs))?>
</ul>
</div><!-- #keys -->

<div id="frame">
<iframe src="<?

if (count($_GET) == 0) {
  echo 'overview.php';
} else {
  $href = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);

  if (strpos($href, '&') !== false) {
    $href = substr_replace($href, '.php?', strpos($href, '&'), 1);
  } else {
    $href .= '.php';
  }

  echo format_html($href);
}

?>" id="iframe" frameborder="0" scrolling="0"></iframe>
</div><!-- #frame -->
</div><!-- #sidebar -->

<?


require 'footer.inc.php';


$redis->close();

