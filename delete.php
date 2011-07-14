<?

require_once 'common.inc.php';



if (isset($_GET['key'])) {
  // String
  if (!isset($_GET['type']) || ($_GET['type'] == 'string')) {
    // Delete the whole key.
    $redis->delete($_GET['key']);
  }

  // Hash
  else if (($_GET['type'] == 'hash') && isset($_GET['hkey'])) {
    // Delete only the field in the hash.
    $redis->hDel($_GET['key'], $_GET['hkey']);
  }

  // List
  else if (($_GET['type'] == 'list') && isset($_GET['index'])) {
    // Lists don't have simple delete operations.
    // You can only remove something based on a value so we set the value at the index to some random value we hope doesn't occur elsewhere in the list.
    $value = str_rand(69);

    // This code assumes $value is not present in the list. To make sure of this we would need to check the whole list and place a Watch on it to make sure the list isn't modified in between.
    $redis->lSet($_GET['key'], $_GET['index'], $value);
    $redis->lRem($_GET['key'], $value, 1);
  }

  // Set
  else if (($_GET['type'] == 'set') && isset($_GET['value'])) {
    // Removing members from a set can only be done by supplying the member.
    $redis->sRem($_GET['key'], $_GET['value']);
  }

  // ZSet
  else if (($_GET['type'] == 'zset') && isset($_GET['value'])) {
    // Removing members from a zset can only be done by supplying the value.
    $redis->zDelete($_GET['key'], $_GET['value']);
  }



  // Refresh the top so the key tree is updated.
  require 'header.inc.php';

  ?>
  <script>
  top.location.href = top.location.pathname+'?view&s=<?=$server['id']?>&key=<?=urlencode($_GET['key'])?>';
  </script>
  <?

  require 'footer.inc.php';
  die;
}

