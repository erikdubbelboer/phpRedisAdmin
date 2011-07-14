<?

require_once 'common.inc.php';




$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2>Saving</h2>

...
<?

// Flush everything so far cause the next command could take some time.
flush();

$redis->save();

?>
 done.
<?

require 'footer.inc.php';

