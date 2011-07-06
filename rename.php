<?

require 'common.inc.php';




if (isset($_POST['old'], $_POST['key'])) {
  $redis->rename($_POST['old'], $_POST['key']);

  require 'header.inc.php';
  ?>
  <script>
  top.location.href = top.location.pathname+'?view&key=<?=format_html($_POST['key'])?>';
  </script>
  <?
  require 'footer.inc.php';
  die;
}



$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2>Edit Name of <?=format_html($_GET['key'])?></h2>
<form action="<?=format_html($_SERVER['REQUEST_URI'])?>" method="post">

<input type="hidden" name="old" value="<?=format_html($_GET['key'])?>">

<p>
<label for="key">Key:</label>
<input type="text" name="key" id="key" size="30" <?=isset($_GET['key']) ? 'value="'.format_html($_GET['key']).'"' : ''?>>
</p>

<p>
<input type="submit" class="button" value="Rename">
</p>

</form>
<?

require 'footer.inc.php';

