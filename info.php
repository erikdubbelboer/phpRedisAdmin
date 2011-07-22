<?php

require_once 'common.inc.php';




if (isset($_GET['reset']) && method_exists($redis, 'resetStat')) {
  $redis->resetStat();

  header('Location: info.php');
  die;
}



// Fetch the info
$info = $redis->info();
$alt  = false;




$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2>Info</h2>

<?php if (method_exists($redis, 'resetStat')) { ?>
<p>
<a href="?reset&amp;s=<?php print $server['id']?>" class="reset">Reset usage statistics</a>
</p>
<?php } ?>

<table>
<tr><th><div>Key</div></th><th><div>Value</div></th></tr>
<?php

foreach ($info as $key => $value) {
  if ($key == 'allocation_stats') { // This key is very long to split it into multiple lines
    $value = str_replace(',', ",\n", $value);
  }

  ?>
  <tr <?php print $alt ? 'class="alt"' : ''?>><td><div><?php print format_html($key)?></div></td><td><div><?php print nl2br(format_html($value))?></div></td></tr>
  <?php

  $alt = !$alt;
}

?>
</table>
<?php

require 'footer.inc.php';

?>