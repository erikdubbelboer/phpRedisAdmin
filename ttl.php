<?php

require_once 'includes/common.inc.php';
global $redis, $config, $csrfToken, $server;

if (isset($_POST['key'], $_POST['ttl'])) {
  if ($_POST['ttl'] == -1) {
    $redis->persist($_POST['key']);
  } else {
    $redis->expire($_POST['key'], $_POST['ttl']);
  }

  header('Location: view.php?key='.urlencode($_POST['key']));
  die;
}

$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'includes/header.inc.php';

?>
<h2>Edit TTL</h2>
<form action="<?php echo format_html(getRelativePath('ttl.php'))?>" method="post">
<input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>" />

<p>
<label for="key">Key:</label>
<input type="text" name="key" id="key" size="30" <?php echo isset($_GET['key']) ? 'value="'.format_html($_GET['key']).'"' : ''?>>
</p>

<p>
<label for="ttl"><abbr title="Time To Live">TTL</abbr>:</label>
<input type="text" name="ttl" id="ttl" size="30" <?php echo isset($_GET['ttl']) ? 'value="'.format_html($_GET['ttl']).'"' : ''?>> <span class="info">(-1 to remove the TTL)</span>
</p>

<input type="submit" class="button" value="Edit TTL">

</form>
<?php

require 'includes/footer.inc.php';

?>
