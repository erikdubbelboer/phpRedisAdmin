<?php

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

  // Containing an item named __phpredisadmin__ means it's also a key.
  // This means that creating an actual key named __phpredisadmin__ will make this bug.
  $d[$key[count($key) - 1]] = array('__phpredisadmin__' => true);

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

  // Is this also a key and not just a namespace?
  if (isset($item['__phpredisadmin__'])) {
    // Unset it so we won't loop over it when printing this namespace.
    unset($item['__phpredisadmin__']);

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
    <li<?php echo empty($class) ? '' : ' class="'.implode(' ', $class).'"'?>>
    <a href="?view&amp;s=<?php echo $server['id']?>&amp;key=<?php echo urlencode($fullkey)?>"><?php echo format_html($name)?><?php if ($len !== false) { ?><span class="info">(<?php echo $len?>)</span><?php } ?></a>
    </li>
    <?php
  }
  
  // Does this namespace also contain subkeys?
  if (count($item) > 0) {
    ?>
    <li class="folder<?php echo empty($fullkey) ? '' : ' collapsed'?><?php echo $islast ? ' last' : ''?>"><div class="icon"><?php echo format_html($name)?> <span class="info">(<?php echo count($item)?>)</span></div>
    <ul>
    <?php

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
    <?php
  }
}




$page['css'][] = 'index';
$page['js'][]  = 'index';

require 'header.inc.php';

?>
<div id="sidebar">

<h1 class="logo"><a href="?overview&amp;s=<?php echo $server['id']?>">phpRedisAdmin</a></h1>

<p>
<select id="server">
<?php foreach ($config['servers'] as $i => $srv) { ?>
<option value="<?php echo $i?>" <?php echo ($server['id'] == $i) ? 'selected="selected"' : ''?>><?php echo isset($srv['name']) ? format_html($srv['name']) : $srv['host'].':'.$srv['port']?></option>
<?php } ?>
</select>
</p>

<p>
<?php if (isset($login)) { ?>
<a href="logout.php"><img src="images/logout.png" width="16" height="16" title="Logout" alt="[L]"></a>
<?php } ?>
<a href="?info&amp;s=<?php echo $server['id']?>"><img src="images/info.png" width="16" height="16" title="Info" alt="[I]"></a>
<a href="?export&amp;s=<?php echo $server['id']?>"><img src="images/export.png" width="16" height="16" title="Export" alt="[E]"></a>
<a href="?import&amp;s=<?php echo $server['id']?>"><img src="images/import.png" width="16" height="16" title="Import" alt="[I]"></a>
</p>

<p>
<a href="?edit&amp;s=<?php echo $server['id']?>" class="add">Add another key</a>
</p>

<div id="keys">
<ul>
<?php print_namespace($namespaces, 'Keys', '', empty($namespaces))?>
</ul>
</div><!-- #keys -->

<div id="frame">
<iframe src="<?php echo format_html($iframe)?>" id="iframe" frameborder="0" scrolling="0"></iframe>
</div><!-- #frame -->

</div><!-- #sidebar -->
<?php

require 'footer.inc.php';

?>
