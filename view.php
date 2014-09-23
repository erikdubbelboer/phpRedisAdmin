<?php

require_once 'includes/common.inc.php';

$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'includes/header.inc.php';



if (!isset($_GET['key'])) {
  ?>
  Invalid key
  <?php

  require 'includes/footer.inc.php';
  die;
}

$key = $_GET['key'];

$type   = $redis->type($key);
$exists = $redis->exists($key);

$count_elements_page = isset($config['count_elements_page']) ? $config['count_elements_page'] : false;
$page_num_request    = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;

//parameters `from` and `to` have higher priority than `page`
//
$cursors = array('index', 'score', 'datetime');
$cursorType = isset ($_GET['cursor']) && in_array($_GET['cursor'], $cursors) ? $_GET['cursor'] : 'index';
if (isset ($_GET['from'])) {
    if ($cursorType == 'datetime') {
        $start = strtotime($_GET['from']);
        $end = isset ($_GET['to']) ? strtotime($_GET['to']) : '+inf';
        $page_num_request = $start / $count_elements_page +1;
    } else {
        $start = $_GET['from'];
        $end = isset ($_GET['to']) ? $_GET['to'] : $count_elements_page -1;
    }
} else {
    $start = ($page_num_request-1) * $count_elements_page;
    $end = $start + $count_elements_page -1;
}


?>
<h2><?php echo format_html($key)?>
<?php if ($exists) { ?>
  <a href="rename.php?s=<?php echo $server['id']?>&amp;key=<?php echo $key?>"><img src="images/edit.png" width="16" height="16" title="Rename" alt="[R]"></a>
  <a href="delete.php?s=<?php echo $server['id']?>&amp;key=<?php echo $key?>" class="delkey"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  <a href="export.php?s=<?php echo $server['id']?>&amp;key=<?php echo $key?>"><img src="images/export.png" width="16" height="16" title="Export" alt="[E]"></a>
<?php } ?>
</h2>
<?php

if (!$exists) {
  ?>
  This key does not exist.
  <?php

  require 'includes/footer.inc.php';
  die;
}



$alt      = false;
$ttl      = $redis->ttl($key);

try {
  $encoding = $redis->object('encoding', $key);
} catch (Exception $e) {
  $encoding = null;
}


switch ($type) {
case 'string':
    $value = $redis->get($key);
    $size  = strlen($value);
    break;

case 'hash':
    $values = $redis->hGetAll($key);
    $size   = count($values);
    break;

  case 'list':
    $size = $redis->lLen($key);
    $values = $redis->lRange($key, $start, $end);
    $count = count($values);
    break;

  case 'set':
    $values = $redis->sMembers($key);
    $size   = count($values);
    break;

  case 'zset':
    $size = $redis->zCard($key);
    if ($cursorType == 'index') {
        $values = $redis->zRange($key, $start, $end, 'WITHSCORES');
    } else {
        $values = $redis->zRangeByScore($key, $start, $end, 
            array('WITHSCORES' =>true,'LIMIT'=>array(0, $count_elements_page))
        );
    }
    $count = count($values);
    break;
}


?>
<table>

<tr><td><div>Type:</div></td><td><div><?php echo format_html($type)?></div></td></tr>

<tr><td><div><abbr title="Time To Live">TTL</abbr>:</div></td><td><div><?php echo ($ttl == -1) ? 'does not expire' : $ttl?> <a href="ttl.php?s=<?php echo $server['id']?>&amp;key=<?php echo $key?>&amp;ttl=<?php echo $ttl?>"><img src="images/edit.png" width="16" height="16" title="Edit TTL" alt="[E]" class="imgbut"></a></div></td></tr>

<?php if (!is_null($encoding)) { ?>
<tr><td><div>Encoding:</div></td><td><div><?php echo format_html($encoding)?></div></td></tr>
<?php } ?>

<tr><td><div>Size:</div></td><td><div><?php echo $size?> <?php echo ($type == 'string') ? 'characters' : 'items'?></div></td></tr>
<?php if (isset ($count)) {?>
<tr><td><div>Returns:</div></td><td><div><?php echo $count?></div></td></tr>
<?php }?>

</table>

<p>
<?php


// Build pagination div.
if (($count_elements_page !== false) && in_array($type, array('hash', 'list', 'set', 'zset')) && ($size > $count_elements_page)) {
  $pagination = '<div style="width: inherit; word-wrap: break-word;">';
  $url        = preg_replace('/&page=(\d+)/i', '', $_SERVER['REQUEST_URI']);

  for ($i = 0; $i < ceil($size / $count_elements_page); ++$i) {
    $page_num = $i + 1;

    if ($page_num === $page_num_request) {
      $pagination .= $page_num . '&nbsp;';
    } else {
      $pagination .= '<a href="' . $url . '&page=' . $page_num . '">' . $page_num . "</a>&nbsp;";
    }
  }

  $pagination .= '</div>';
}


if (isset($pagination) && strlen($pagination) < 2048) {
  echo $pagination;
}


// String
if ($type == 'string') { 
    $value_unsrlzd = @unserialize($value);
    if ($value_unsrlzd != null) { // unserialize success!
        $value_export = var_export($value_unsrlzd, true);
        $value = $value_export;
    }
?>

<table>
<tr><td><div><?php echo nl2br(format_html($value, $server['charset']))?></div></td><td><div>
  <a href="edit.php?s=<?php echo $server['id']?>&amp;type=string&amp;key=<?php echo $key?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
</div></td><td><div>
  <a href="delete.php?s=<?php echo $server['id']?>&amp;type=string&amp;key=<?php echo key?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
</div></td></tr>
</table>

<?php }



// Hash
else if ($type == 'hash') { ?>

<table>
<tr><th><div>Key</div></th><th><div>Value</div></th><th><div>&nbsp;</div></th><th><div>&nbsp;</div></th></tr>

<?php foreach ($values as $hkey => $value) { 
    $value_unsrlzd = @unserialize($value);
    if ($value_unsrlzd != null) { // unserialize success!
        $value_export = var_export($value_unsrlzd, true);
        $value = $value_export;
    }
?>
  <tr <?php echo $alt ? 'class="alt"' : ''?>><td><div><?php echo format_html($hkey, $server['charset'])?></div></td><td><div><?php echo nl2br(format_html($value, $server['charset']))?></div></td><td><div>
    <a href="edit.php?s=<?php echo $server['id']?>&amp;type=hash&amp;key=<?php echo $key?>&amp;hkey=<?php echo $hkey?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
  </div></td><td><div>
    <a href="delete.php?s=<?php echo $server['id']?>&amp;type=hash&amp;key=<?php echo $key?>&amp;hkey=<?php echo $hkey?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  </div></td></tr>
<?php $alt = !$alt; } ?>

<?php }


// List
else if ($type == 'list') { ?>

<table>
<tr><th><div>Index</div></th><th><div>Value</div></th><th><div>&nbsp;</div></th><th><div>&nbsp;</div></th></tr>

<?php 
foreach ($values as $i => $value) {
?>
  <tr <?php echo $alt ? 'class="alt"' : ''?>><td><div><?php echo $i?></div></td><td><div><?php echo nl2br(format_html($value, $server['charset']))?></div></td><td><div>
    <a href="edit.php?s=<?php echo $server['id']?>&amp;type=list&amp;key=<?php echo $key?>&amp;index=<?php echo $i?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
  </div></td><td><div>
    <a href="delete.php?s=<?php echo $server['id']?>&amp;type=list&amp;key=<?php echo $key?>&amp;index=<?php echo $i?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  </div></td></tr>
<?php $alt = !$alt; } ?>

<?php }



// Set
else if ($type == 'set') {

?>
<table>
<tr><th><div>Value</div></th><th><div>&nbsp;</div></th><th><div>&nbsp;</div></th></tr>

<?php foreach ($values as $value) {
  $display_value = $redis->exists($value) ? '<a href="view.php?s='.$server['id'].'&key='.$value.'">'.nl2br(format_html($value, $server['charset'])).'</a>' : nl2br(format_html($value, $server['charset']));
?>
  <tr <?php echo $alt ? 'class="alt"' : ''?>><td><div><?php echo $display_value ?></div></td><td><div>
    <a href="edit.php?s=<?php echo $server['id']?>&amp;type=set&amp;key=<?php echo $key?>&amp;value=<?php echo $value?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
  </div></td><td><div>
    <a href="delete.php?s=<?php echo $server['id']?>&amp;type=set&amp;key=<?php echo $key?>&amp;value=<?php echo $value?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  </div></td></tr>
<?php $alt = !$alt; } ?>

<?php }



// ZSet
else if ($type == 'zset') { ?>

<table>
<tr><th><div>Score</div></th><th><div>Value</div></th><th><div>Parse score as date</div></th><th><div>&nbsp;</div></th><th><div>&nbsp;</div></th></tr>

<?php foreach ($values as $value => $score) {
    $score_parsed = $score > 1300000000 ? @date('Y-m-d H:i:s', (int)$score) : 0;
    $value_unsrlzd = @unserialize($value);
    if ($value_unsrlzd != null){ // unserialize success!
        $value = var_export($value_unsrlzd, true);
    }
    $display_value = $redis->exists($value) ? '<a href="view.php?s='.$server['id'].'&key='.$value.'">'.nl2br(format_html($value, $server['charset'])).'</a>' : nl2br(format_html($value, $server['charset']));
?>
  <tr <?php echo $alt ? 'class="alt"' : ''?>><td><div><?php echo $score?></div></td><td><div><?php echo $display_value ?></div></td><td><div><?php echo $score_parsed ?></div></td><td><div>
    <a href="edit.php?s=<?php echo $server['id']?>&amp;type=zset&amp;key=<?php echo $key?>&amp;score=<?php echo $score?>&amp;value=<?php echo $value?>"><img src="images/edit.png" width="16" height="16" title="Edit" alt="[E]"></a>
    <a href="delete.php?s=<?php echo $server['id']?>&amp;type=zset&amp;key=<?php echo $key?>&amp;value=<?php echo $value?>" class="delval"><img src="images/delete.png" width="16" height="16" title="Delete" alt="[X]"></a>
  </div></td></tr>
<?php $alt = !$alt; } ?>

<?php }

if ($type != 'string') { ?>
  </table>

  <p>
  <a href="edit.php?s=<?php echo $server['id']?>&amp;type=<?php echo $type?>&amp;key=<?php echo $key?>" class="add">Add another value</a>
  </p>
<?php }

if (isset($pagination)) {
  echo $pagination;
}

require 'includes/footer.inc.php';

