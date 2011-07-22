<?php

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: private');

?><!DOCTYPE html>
<html lang=nl>
<head>
<meta charset=utf-8>

<?php if (is_ie()) {
  // Always force latest IE rendering engine and chrome frame (also hides compatibility mode button)
  ?><meta http-equiv=X-UA-Compatible content="IE=edge,chrome=1"><?php
} ?>

<meta name=author content="https://github.com/ErikDubbelboer/">

<?php /* I don't think we ever want this to be indexed*/ ?>
<meta name=robots content="noindex,nofollow,noarchive">

<title><?php print format_html($server['host'])?> - phpRedisAdmin</title>

<?php foreach ($page['css'] as $css) { ?>
<link rel=stylesheet href="css/<?php print $css; ?>.css?v1" media=all>
<?php } ?>

<link rel="shortcut icon" href="favicon.ico">

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.js"></script>

<?php foreach ($page['js'] as $js) { ?>
<script src="js/<?php print $js; ?>.js?v1"></script>
<?php } ?>

</head>
<body>
