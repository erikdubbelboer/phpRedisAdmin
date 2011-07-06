<?

require 'common.inc.php';




$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2>Saving</h2>

...
<?

flush();

$redis->save();

?>
done.
<?

require 'footer.inc.php';

