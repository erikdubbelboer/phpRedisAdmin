<?

require 'common.inc.php';




if (isset($_POST['type'], $_POST['key'], $_POST['value'])) {
  if ($_POST['type'] == 'string') {
    $redis->set($_POST['key'], $_POST['value']);
  } else if (($_POST['type'] == 'hash') && isset($_POST['hkey'])) {
    $redis->hSet($_POST['key'], $_POST['hkey'], $_POST['value']);
  } else if (($_POST['type'] == 'list') && isset($_POST['index'])) {
    $size = $redis->lSize($_POST['key']);

    if (empty($_POST['index']) || ($_POST['index'] == $size)) {
      $redis->rPush($_POST['key'], $_POST['value']);
    } else if ($_POST['index'] == -1) {
      $redis->lPush($_POST['key'], $_POST['value']);
    } else if (($_POST['index'] >= 0) && ($_POST['index'] < $size)) {
      $redis->lSet($_POST['key'], $_POST['index'], $_POST['value']);
    }
  } else if ($_POST['type'] == 'set') {
    $redis->sAdd($_POST['key'], $_POST['value']);
  } else if (($_POST['type'] == 'zset') && isset($_POST['score'])) {
    $redis->zAdd($_POST['key'], $_POST['score'], $_POST['value']);
  }

  require 'header.inc.php';
  ?>
  <script>
  top.location.href = top.location.pathname+'?view&key=<?=format_html($_POST['key'])?>';
  </script>
  <?
  require 'footer.inc.php';
  die;
}




$edit = false;

if (isset($_GET['key'], $_GET['type'])) {
  if (($_GET['type'] == 'string') ||
      (($_GET['type'] == 'hash') && isset($_GET['hkey']))  ||
      (($_GET['type'] == 'list') && isset($_GET['index'])) ||
      (($_GET['type'] == 'set' ) && isset($_GET['value'])) ||
      (($_GET['type'] == 'zset') && isset($_GET['value']))) {
    $edit = true;
  }
}


$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2><?=$edit ? 'Edit' : 'Add'?></h2>
<form action="<?=format_html($_SERVER['REQUEST_URI'])?>" method="post">

<p>
<label for="type">Type:</label>
<select name="type" id="type">
<option value="string" <?=(isset($_GET['type']) && ($_GET['type'] == 'string')) ? 'selected="selected"' : ''?>>String</option>
<option value="hash"   <?=(isset($_GET['type']) && ($_GET['type'] == 'hash'  )) ? 'selected="selected"' : ''?>>Hash</option>
<option value="list"   <?=(isset($_GET['type']) && ($_GET['type'] == 'list'  )) ? 'selected="selected"' : ''?>>List</option>
<option value="set"    <?=(isset($_GET['type']) && ($_GET['type'] == 'set'   )) ? 'selected="selected"' : ''?>>Set</option>
<option value="zset"   <?=(isset($_GET['type']) && ($_GET['type'] == 'zset'  )) ? 'selected="selected"' : ''?>>ZSet</option>
</select>
</p>

<p>
<label for="key">Key:</label>
<input type="text" name="key" id="key" size="30" <?=isset($_GET['key']) ? 'value="'.format_html($_GET['key']).'"' : ''?>>
</p>

<p id="hkeyp">
<label for="khey">Hash key:</label>
<input type="text" name="hkey" id="hkey" size="30" <?=isset($_GET['hkey']) ? 'value="'.format_html($_GET['hkey']).'"' : ''?>>
</p>

<p id="indexp">
<label for="index">Index:</label>
<input type="text" name="index" id="index" size="30" <?=isset($_GET['index']) ? 'value="'.format_html($_GET['index']).'"' : ''?>> <span class="info">empty to append, -1 to prepend</span>
</p>

<p id="scorep">
<label for="score">Score:</label>
<input type="text" name="score" id="score" size="30" <?=isset($_GET['score']) ? 'value="'.format_html($_GET['score']).'"' : ''?>>
</p>

<p>
<label>Value:</label>
<textarea name="value" cols="80" rows="20"><?
  if ($edit) {
    if ($_GET['type'] == 'string') {
      echo nl2br(format_html($redis->get($_GET['key'])));
    } else if (($_GET['type'] == 'hash') && isset($_GET['hkey'])) {
      echo nl2br(format_html($redis->hGet($_GET['key'], $_GET['hkey'])));
    } else if (($_GET['type'] == 'list') && isset($_GET['index'])) {
      echo nl2br(format_html($redis->lGet($_GET['key'], $_GET['index'])));
    } else if ((($_GET['type'] == 'set') || ($_GET['type'] == 'zset')) && isset($_GET['value'])) {
      echo nl2br(format_html($_GET['value']));
    }
  }
?></textarea>
</p>

<p>
<input type="submit" class="button" value="<?=$edit ? 'Edit' : 'Add'?>">
</p>

</form>
<?

require 'footer.inc.php';

