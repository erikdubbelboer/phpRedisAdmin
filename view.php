<?

require_once 'common.inc.php';




$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';



if (!isset($_GET['key'])) {
  ?>
  Invalid key
  <?

  require 'footer.inc.php';
  die;
}



$type   = $redis->type($_GET['key']);
$exists = $redis->exists($_GET['key']);


?>
<h2><?=format_html($_GET['key'])?>
<? if ($exists) { ?>
  <a href="rename.php?s=<?=$server['id']?>&amp;key=<?=urlencode($_GET['key'])?>"><img src="images/edit.png" width="16" height="16" title="Rename" alt="[R]"></a>
  <a href="delete.php?s=<?=$server['id']?>&amp;key=<?=urlencode($_GET['key'])?>" class="delkey"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  <a href="export.php?s=<?=$server['id']?>&amp;key=<?=urlencode($_GET['key'])?>"><img src="images/export.png" width="16" height="16" title="Export" alt="[E]"></a>
<? } ?>
</h2>
<?

if (!$exists) {
  ?>
  This key does not exist.
  <?

  require 'footer.inc.php';
  die;
}



$alt      = false;
$type     = $redistypes[$type];
$ttl      = $redis->ttl($_GET['key']);
$encoding = $redis->object('encoding', $_GET['key']);


switch ($type) {
  case 'string':
    $value = $redis->get($_GET['key']);
    $size  = strlen($value);
    break;

  case 'hash':
    $values = $redis->hGetAll($_GET['key']);
    $size   = count($values);
    break;

  case 'list':
    $size = $redis->lSize($_GET['key']);
    break;

  case 'set':
    $values = $redis->sMembers($_GET['key']);
    $size   = count($values);
    break;

  case 'zset':
    $values = $redis->zRange($_GET['key'], 0, -1);
    $size   = count($values);
    break;
}


?>
<table>

<tr><td><div>Type:</div></td><td><div><?=format_html($type)?></div></td></tr>

<tr><td><div><abbr title="Time To Live">TTL</abbr>:</div></td><td><div><?=($ttl == -1) ? 'does not expire' : $ttl?> <a href="ttl.php?s=<?=$server['id']?>&amp;key=<?=urlencode($_GET['key'])?>&amp;ttl=<?=$ttl?>"><img src="images/edit.png" width="16" height="16" title="Edit TTL" alt="[E]" class="imgbut"></a></div></td></tr>

<tr><td><div>Encoding:</div></td><td><div><?=format_html($encoding)?></div></td></tr>

<tr><td><div>Size:</div></td><td><div><?=$size?> <?=($type == 'string') ? 'characters' : 'items'?></div></td></tr>

</table>

<p>
<?



// String
if ($type == 'string') { ?>

<table>
<tr><td><div><?=nl2br(format_html($value))?></div></td><td><div>
  <a href="edit.php?s=<?=$server['id']?>&amp;type=string&amp;key=<?=urlencode($_GET['key'])?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
</div></td><td><div>
  <a href="delete.php?s=<?=$server['id']?>&amp;type=string&amp;key=<?=urlencode($_GET['key'])?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
</div></td></tr>
</table>

<? } 



// Hash
else if ($type == 'hash') { ?>

<table>
<tr><th><div>Key</div></th><th><div>Value</div></th><th><div>&nbsp;</div></th><th><div>&nbsp;</div></th></tr>

<? foreach ($values as $hkey => $value) { ?>
  <tr <?=$alt ? 'class="alt"' : ''?>><td><div><?=format_html($hkey)?></div></td><td><div><?=nl2br(format_html($value))?></div></td><td><div>
    <a href="edit.php?s=<?=$server['id']?>&amp;type=hash&amp;key=<?=urlencode($_GET['key'])?>&amp;hkey=<?=urlencode($hkey)?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
  </div></td><td><div>
    <a href="delete.php?s=<?=$server['id']?>&amp;type=hash&amp;key=<?=urlencode($_GET['key'])?>&amp;hkey=<?=urlencode($hkey)?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  </div></td></tr>
<? $alt = !$alt; } ?>

<? }



// List
else if ($type == 'list') { ?>
      
<table>
<tr><th><div>Index</div></th><th><div>Value</div></th><th><div>&nbsp;</div></th><th><div>&nbsp;</div></th></tr>

<? for ($i = 0; $i < $size; ++$i) {
  $value = $redis->lGet($_GET['key'], $i);
?>
  <tr <?=$alt ? 'class="alt"' : ''?>><td><div><?=$i?></div></td><td><div><?=nl2br(format_html($value))?></div></td><td><div>
    <a href="edit.php?s=<?=$server['id']?>&amp;type=list&amp;key=<?=urlencode($_GET['key'])?>&amp;index=<?=$i?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
  </div></td><td><div>
    <a href="delete.php?s=<?=$server['id']?>&amp;type=list&amp;key=<?=urlencode($_GET['key'])?>&amp;index=<?=$i?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  </div></td></tr>
<? $alt = !$alt; } ?>

<? }



// Set
else if ($type == 'set') {

?>
<table>
<tr><th><div>Value</div></th><th><div>&nbsp;</div></th><th><div>&nbsp;</div></th></tr>

<? foreach ($values as $value) { ?>
  <tr <?=$alt ? 'class="alt"' : ''?>><td><div><?=nl2br(format_html($value))?></div></td><td><div>
    <a href="edit.php?s=<?=$server['id']?>&amp;type=set&amp;key=<?=urlencode($_GET['key'])?>&amp;value=<?=urlencode($value)?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
  </div></td><td><div>
    <a href="delete.php?s=<?=$server['id']?>&amp;type=set&amp;key=<?=urlencode($_GET['key'])?>&amp;value=<?=urlencode($value)?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  </div></td></tr>
<? $alt = !$alt; } ?>

<? }



// ZSet
else if ($type == 'zset') { ?>

<table>
<tr><th><div>Score</div></th><th><div>Value</div></th><th><div>&nbsp;</div></th><th><div>&nbsp;</div></th></tr>

<? foreach ($values as $value) {
  $score = $redis->zScore($_GET['key'], $value);
?>
  <tr <?=$alt ? 'class="alt"' : ''?>><td><div><?=$score?></div></td><td><div><?=nl2br(format_html($value))?></div></td><td><div>
    <a href="edit.php?s=<?=$server['id']?>&amp;type=zset&amp;key=<?=urlencode($_GET['key'])?>&amp;score=<?=$score?>&amp;value=<?=urlencode($value)?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
    <a href="delete.php?s=<?=$server['id']?>&amp;type=zset&amp;key=<?=urlencode($_GET['key'])?>&amp;value=<?=urlencode($value)?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  </div></td></tr>
<? $alt = !$alt; } ?>

<? }




if ($type != 'string') { ?>
  </table>

  <p>
  <a href="edit.php?s=<?=$server['id']?>&amp;type=<?=$type?>&amp;key=<?=urlencode($_GET['key'])?>" class="add">Add another value</a>
  </p>
<? }


require 'footer.inc.php';

