<?

require_once 'common.inc.php';




// Get all keys from Redis.
$keys = $redis->keys('*');

sort($keys);

$namespaces = array(); // Array to hold our top namespaces.

// Build an array of nested arrays containing all our namespaces and containing keys.
foreach ($keys as $key) {
  // Ignore keys that are to long (Redis supports keys that can be way to long to put in an url).
  if (strlen($key) > $config['maxkeylen']) {
    continue;
  }

  $key = explode($config['seperator'], $key);

  // $d will be a reference to the current namespace.
  $d = &$namespaces;

  // We loop though all the namespaces for this key creating the array for each.
  // Each time updating $d to be a reference to the last namespace so we can create the next one in it.
  for ($i = 0; $i < (count($key) - 1); ++$i) {
    if (!isset($d[$key[$i]])) {
      $d[$key[$i]] = array();
    }

    $d = &$d[$key[$i]];
  }

  $d[$key[count($key) - 1]] = true; // true means this is an actual key.

  // Unset $d so we don't accidentally overwrite it somewhere else.
  unset($d);
}



// This is basically the same as the click code in index.js.
// Just build the url for the frame based on our own url.
if (count($_GET) == 0) {
  $iframe = 'overview.php';
} else {
  $iframe = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);

  if (strpos($iframe, '&') !== false) {
    $iframe = substr_replace($iframe, '.php?', strpos($iframe, '&'), 1);
  } else {
    $iframe .= '.php';
  }
}






// Recursive function used to print the namespaces.
function print_namespace($item, $name, $fullkey, $islast) {
  global $config, $redistypes, $server, $redis;


  // true means it's a key and not a namespace.
  if ($item === true) {
    $type = $redis->type($fullkey);

    if (!isset($redistypes[$type])) {
      return;
    }

    $type  = $redistypes[$type];
    $class = array();
    $len   = false;

    if (isset($_GET['key']) && ($fullkey == $_GET['key'])) {
      $class[] = 'current';
    }
    if ($islast) {
      $class[] = 'last';
    }

    // Get the number of items in the key.
    if (!isset($config['faster']) || !$config['faster']) {
      switch ($type) {
        case 'hash':
          $len = $redis->hLen($fullkey);
          break;

        case 'list':
          $len = $redis->lSize($fullkey);
          break;

        case 'set':
          // This is currently the only way to do this, this can be slow since we need to retrieve all keys
          $len = count($redis->sMembers($fullkey));
          break;

        case 'zset':
          // This is currently the only way to do this, this can be slow since we need to retrieve all keys
          $len = count($redis->zRange($fullkey, 0, -1));
          break;
      }
    }


    ?>
    <li<?=empty($class) ? '' : ' class="'.implode(' ', $class).'"'?>>
    <a href="?view&amp;s=<?=$server['id']?>&amp;key=<?=urlencode($fullkey)?>"><?=format_html($name)?><? if ($len !== false) { ?><span class="info">(<?=$len?>)</span><? } ?></a>
    </li>
    <?
  } else { // It's a namespace, recursively call this function on all it's members.
    ?>
    <li class="folder<?=empty($fullkey) ? '' : ' collapsed'?><?=$islast ? ' last' : ''?>"><div class="icon"><?=format_html($name)?> <span class="info">(<?=count($item)?>)</span></div>
    <ul>
    <?

    $l = count($item);

    foreach ($item as $childname => $childitem) {
      // $fullkey will be empty on the first call.
      if (empty($fullkey)) {
        $childfullkey = $childname;
      } else {
        $childfullkey = $fullkey.$config['seperator'].$childname;
      }

      print_namespace($childitem, $childname, $childfullkey, (--$l == 0));
    }

    ?>
    </ul>
    </li>
    <?
  }
}




$page['css'][] = 'index';
$page['js'][]  = 'index';

require 'header.inc.php';

?>
<div id="sidebar">

<h1 class="logo"><a href="?overview&amp;s=<?=$server['id']?>">phpRedisAdmin</a></h1>

<p>
<select id="server">
<? foreach ($config['servers'] as $i => $srv) { ?>
<option value="<?=$i?>" <?=($server['id'] == $i) ? 'selected="selected"' : ''?>><?=isset($srv['name']) ? format_html($srv['name']) : $srv['host'].':'.$srv['port']?></option>
<? } ?>
</select>
</p>

<p>
<a href="?info&amp;s=<?=$server['id']?>"><img src="images/info.png" width="16" height="16" title="Info" alt="[I]"></a>
<a href="?export&amp;s=<?=$server['id']?>"><img src="images/export.png" width="16" height="16" title="Export" alt="[E]"></a>
</p>

<p>
<a href="?edit&amp;s=<?=$server['id']?>" class="add">Add another key</a>
</p>

<div id="keys">
<ul>
<?print_namespace($namespaces, 'Keys', '', empty($namespaces))?>
</ul>
</div><!-- #keys -->

<div id="frame">
<iframe src="<?=format_html($iframe)?>" id="iframe" frameborder="0" scrolling="0"></iframe>
</div><!-- #frame -->

</div><!-- #sidebar -->
<?

require 'footer.inc.php';

