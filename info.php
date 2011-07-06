<?

require 'common.inc.php';




$info = $redis->info();
$alt  = false;


$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2>Info</h2>

<table>
<tr><th><div>Key</div></th><th><div>Value</div></th></tr>
<?

foreach ($info as $key => $value) {
  if ($key == 'allocation_stats') {
    $value = str_replace(',', ",\n", $value);
  }

  ?>
  <tr <?=$alt ? 'class="alt"' : ''?>><td><div><?=format_html($key)?></div></td><td><div><?=nl2br(format_html($value))?></div></td></tr>
  <?

  $alt = !$alt;
}

?>
</table>
<?

require 'footer.inc.php';

