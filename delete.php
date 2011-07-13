<?

require 'common.inc.php';



if (isset($_GET['key'])) {
  if (!isset($_GET['type']) || ($_GET['type'] == 'string')) {
    $redis->delete($_GET['key']);
  } else if (($_GET['type'] == 'hash') && isset($_GET['hkey'])) {
    $redis->hDel($_GET['key'], $_GET['hkey']);
  } else if (($_GET['type'] == 'list') && isset($_GET['index'])) {
    $value = str_rand(64);

    // This code assumes $value is not present in the list. To make sure of this we would need to check the whole list and place a Watch on it to make the list isn't modified in between.
    $redis->lSet($_GET['key'], $_GET['index'], $value);
    $redis->lRem($_GET['key'], $value, 0);
  } else if (($_GET['type'] == 'set') && isset($_GET['value'])) {
    $redis->sRem($_GET['key'], $_GET['value']);
  } else if (($_GET['type'] == 'zset') && isset($_GET['value'])) {
    $redis->zDelete($_GET['key'], $_GET['value']);
  }

  require 'header.inc.php';
  ?>
  <script>
  top.location.href = top.location.pathname+'?view&key=<?=urlencode($_GET['key'])?>';
  </script>
  <?
  require 'footer.inc.php';
  die;
}

