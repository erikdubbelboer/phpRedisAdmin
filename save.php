<?php

require_once 'includes/common.inc.php';
global $redis, $config, $csrfToken, $server;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  die('Save requires a POST request.');
}

$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'includes/header.inc.php';

?>
<h2>Saving</h2>

...
<?php

// Flush everything so far cause the next command could take some time.
flush();

$redis->save();

?>
 done.
<?php

require 'includes/footer.inc.php';

?>
