<?

require 'common.inc.php';




$info = $redis->info();


$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'header.inc.php';

?>
<h2><?=format_html($config['host'])?></h2>

<p>
<table>

<tr><td><div>Redis version:</div></td><td><div><?=$info['redis_version']?></div></td></tr>

<tr><td><div>Keys:</div></td><td><div><?=$redis->dbSize()?></div></td></tr>

<tr><td><div>Memory used:</div></td><td><div><?=format_size($info['used_memory'])?></div></td></tr>

<tr><td><div>Uptime:</div></td><td><div><?=format_ago($info['uptime_in_seconds'])?></div></td></tr>

<tr><td><div>Last save:</div></td><td><div><?=format_ago(time() - $info['last_save_time'], true)?> <a href="save.php"><img src="images/save.png" width="16" height="16" title="Save Now" alt="[S]" class="imgbut"></a></div></td></tr>

</table>
</p>

<p>
<a href="http://redis.io/documentation" target="_blank">Redis Documentation</a>
</p>
<?

require 'footer.inc.php';

