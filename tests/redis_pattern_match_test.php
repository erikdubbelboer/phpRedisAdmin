<?php

// FINDING_TAG: PRA-L3-FILT-CLIENT-OVERRIDE

require_once dirname(__DIR__) . '/includes/functions.inc.php';

$failed = 0;

function fail_case($pattern, $key, $expected, $actual, $description) {
  global $failed;
  ++$failed;
  fwrite(STDERR, sprintf(
    "FAIL pattern=%s key=%s expected=%s actual=%s (%s)\n",
    var_export($pattern, true),
    var_export($key, true),
    $expected ? 'true' : 'false',
    $actual ? 'true' : 'false',
    $description
  ));
}

function run_cases($cases, $matcher) {
  foreach ($cases as $case) {
    list($pattern, $key, $expected, $description) = $case;
    $actual = call_user_func($matcher, $pattern, $key);
    if ($actual !== $expected) {
      fail_case($pattern, $key, $expected, $actual, $description);
    }
  }
}

function match_signed($pattern, $key) {
  return redis_pattern_matches_abi($pattern, $key, true);
}

function match_unsigned($pattern, $key) {
  return redis_pattern_matches_abi($pattern, $key, false);
}

$ascii_cases = array(
  array('[!a]*', 'apple', true, 'Redis treats ! as a class member'),
  array('[!a]*', '!key', true, 'Redis treats ! as a class member'),
  array('[!a]*', 'banana', false, 'Redis does not treat ! as class negation'),
  array('[^a]*', 'apple', false, 'Redis uses ^ for class negation'),
  array('[^a]*', 'banana', true, 'Redis uses ^ for class negation'),
  array('app:*', 'app:key', true, 'star matches a suffix'),
  array('app:*', 'other:key', false, 'literal prefix remains required'),
  array('a?c', 'abc', true, 'question mark matches one byte'),
  array('a?c', 'ac', false, 'question mark requires one byte'),
  array('literal\\*', 'literal*', true, 'backslash escapes a wildcard'),
  array('literal\\*', 'literalX', false, 'escaped wildcard is literal'),
  array('[z-a]', 'm', true, 'Redis accepts a reversed range'),
  array('[z-a]', '0', false, 'range excludes bytes outside its endpoints'),
  array('foo**bar', 'foo-value-bar', true, 'consecutive stars are equivalent'),
  array('foo**bar', 'foo-value-baz', false, 'suffix remains required'),
  array('[\\a-z]', 'm', false, 'escaped class member does not create a-z range'),
  array('[\\a-z]', 'a', true, 'escaped class member matches its literal byte'),
  array('[\\a-z]', 'q', false, 'escaped class member: q is not a range member')
);

// Signed char (Redis 7.0.12 amd64): [a-\xC3] swaps to [-61,97].
$signed_cases = array(
  array("[a-\xC3]*", 'banana', false, 'signed: b=98 outside folded [-61,97]'),
  array("[a-\xC3]*", "\xFF", true, 'signed: \\xFF=-1 inside folded [-61,97]'),
  array("[a-\xC3]*", "\x80", false, 'signed: \\x80=-128 below -61'),
  array("[a-\xC3]*", "\xC3", true, 'signed: \\xC3=-61 is the boundary'),
  array("[^a-\xC3]*", 'banana', true, 'signed: negated folded range includes b=98'),
  array("[\x7F-\x80]*", 'b', true, 'signed: [127,-128] swaps to every byte'),
  array("[\x80-\xFF]*", 'b', false, 'signed: [-128,-1] excludes ASCII'),
  array("[\x80-\xFF]*", "\x80", true, 'signed: [-128,-1] includes \\x80'),
  array("[a-\xC3\xA9]*", 'banana', false, 'amd64 maintainer: [a-e-acute]* excludes banana'),
  array("[a-\xC3\xA9]*", 'apple', true, 'amd64 maintainer: [a-e-acute]* includes apple'),
  array("[^a-\xC3\xA9]*", 'banana', true, 'signed: [^a-e-acute]* includes banana')
);

// Unsigned char (Redis 7.0.12 arm64): [a-\xC3] is [97,195].
$unsigned_cases = array(
  array("[a-\xC3]*", 'banana', true, 'unsigned: b=98 inside [97,195]'),
  array("[a-\xC3]*", "\xFF", false, 'unsigned: \\xFF=255 above 195'),
  array("[a-\xC3]*", "\x80", true, 'unsigned: \\x80=128 inside [97,195]'),
  array("[a-\xC3]*", "\xC3", true, 'unsigned: \\xC3=195 is the boundary'),
  array("[^a-\xC3]*", 'banana', false, 'unsigned: negated [97,195] excludes b=98'),
  array("[\x7F-\x80]*", 'b', false, 'unsigned: [127,128] excludes ASCII b'),
  array("[\x80-\xFF]*", 'b', false, 'unsigned: [128,255] excludes ASCII'),
  array("[\x80-\xFF]*", "\x80", true, 'unsigned: [128,255] includes \\x80'),
  array("[a-\xC3\xA9]*", 'banana', true, 'arm64: [a-e-acute]* includes banana'),
  array("[a-\xC3\xA9]*", 'apple', true, 'arm64: [a-e-acute]* includes apple'),
  array("[^a-\xC3\xA9]*", 'banana', false, 'arm64 maintainer: [^a-e-acute]* excludes banana')
);

// Portable guard: AND of both ABIs. Never allow a key either Redis ABI excludes.
$portable_cases = array(
  array("[a-\xC3\xA9]*", 'banana', false, 'portable: [a-e-acute]* banana denied (amd64 Redis excludes)'),
  array("[a-\xC3\xA9]*", 'apple', true, 'portable: [a-e-acute]* apple allowed on both ABIs'),
  array("[^a-\xC3\xA9]*", 'banana', false, 'portable: [^a-e-acute]* banana denied (arm64 Redis excludes)'),
  array("[a-\xC3]*", 'banana', false, 'portable: signed/unsigned split on banana is deny'),
  array("[^a-\xC3]*", 'banana', false, 'portable: negated split on banana is deny'),
  array("[a-\xC3]*", "\xC3", true, 'portable: \\xC3 is a boundary on both ABIs'),
  array("[a-\xC3\xA9]*", "\xA9", true, 'portable: leftover class member \\xA9 matches both'),
  array("[\x80-\xFF]*", "\x80", true, 'portable: high-byte range agrees on \\x80'),
  array("[\x80-\xFF]*", 'b', false, 'portable: high-byte range excludes ASCII on both'),
  array("[\x7F-\x80]*", 'b', false, 'portable: wrap-range only matches signed, so deny')
);

run_cases($ascii_cases, 'redis_pattern_matches');
run_cases($ascii_cases, 'match_signed');
run_cases($ascii_cases, 'match_unsigned');
run_cases($signed_cases, 'match_signed');
run_cases($unsigned_cases, 'match_unsigned');
run_cases($portable_cases, 'redis_pattern_matches');

$server = array('filter' => '[!a]*');
if (!key_matches_server_filter('apple') || !key_matches_server_filter('!key') || key_matches_server_filter('banana')) {
  ++$failed;
  fwrite(STDERR, "FAIL key_matches_server_filter does not use Redis pattern semantics\n");
}

$server = array('filter' => "[a-\xC3\xA9]*");
if (key_matches_server_filter('banana') || !key_matches_server_filter('apple')) {
  ++$failed;
  fwrite(STDERR, "FAIL key_matches_server_filter [a-e-acute]* amd64 maintainer case\n");
}

$server = array('filter' => "[^a-\xC3\xA9]*");
if (key_matches_server_filter('banana')) {
  ++$failed;
  fwrite(STDERR, "FAIL key_matches_server_filter [^a-e-acute]* arm64 maintainer case\n");
}

$mixed = array('apple', 'banana');
$server = array('filter' => "[a-\xC3\xA9]*");
$locked = filter_keys_for_server($mixed);
if ($locked !== array('apple')) {
  ++$failed;
  fwrite(STDERR, "FAIL filter_keys_for_server does not lock [a-e-acute]* against banana\n");
}
$server = array('filter' => "[^a-\xC3\xA9]*");
$locked = filter_keys_for_server($mixed);
if (in_array('banana', $locked, true)) {
  ++$failed;
  fwrite(STDERR, "FAIL filter_keys_for_server does not lock [^a-e-acute]* against banana\n");
}

function redis_resp_read($fp) {
  $line = fgets($fp);
  if ($line === false) {
    throw new RuntimeException('redis eof');
  }
  $line = substr($line, 0, -2);
  $type = $line[0];
  $rest = substr($line, 1);
  if ($type === '+' || $type === '-') {
    if ($type === '-') {
      throw new RuntimeException($rest);
    }
    return $rest;
  }
  if ($type === ':') {
    return intval($rest, 10);
  }
  if ($type === '$') {
    $n = intval($rest, 10);
    if ($n < 0) {
      return null;
    }
    $data = '';
    while (strlen($data) < $n) {
      $chunk = fread($fp, $n - strlen($data));
      if ($chunk === false || $chunk === '') {
        throw new RuntimeException('redis bulk eof');
      }
      $data .= $chunk;
    }
    fread($fp, 2);
    return $data;
  }
  if ($type === '*') {
    $n = intval($rest, 10);
    if ($n < 0) {
      return null;
    }
    $out = array();
    for ($i = 0; $i < $n; $i++) {
      $out[] = redis_resp_read($fp);
    }
    return $out;
  }
  throw new RuntimeException('redis bad type '.$type);
}

function redis_resp_cmd($fp, $args) {
  $out = '*'.count($args)."\r\n";
  foreach ($args as $a) {
    $out .= '$'.strlen($a)."\r\n".$a."\r\n";
  }
  fwrite($fp, $out);
  return redis_resp_read($fp);
}

function redis_try_connect($host, $port) {
  $fp = @fsockopen($host, $port, $errno, $errstr, 1);
  if (!$fp) {
    return false;
  }
  stream_set_timeout($fp, 3);
  return $fp;
}

function live_redis_check($label, $host, $port, $signed) {
  global $failed, $server;
  $fp = redis_try_connect($host, $port);
  if (!$fp) {
    fwrite(STDERR, "SKIP live Redis $label $host:$port unreachable\n");
    return 0;
  }

  $keys = array(
    'apple', 'banana', '!key', 'zoo', 'a', 'z', 'm',
    'abc', 'ac', "\x00", "\x7F", "\x80", "\xC2", "\xC3", "\xC3\xA9", "\xA9", "\xFF"
  );
  $patterns = array(
    '[!a]*', '[^a]*', '[z-a]',
    "[a-\xC3]*", "[^a-\xC3]*", "[a-\xC3\xA9]*", "[^a-\xC3\xA9]*",
    "[\x7F-\x80]*", "[\x80-\xFF]*", "[\x00-\x7F]*", '*'
  );

  redis_resp_cmd($fp, array('FLUSHDB'));
  foreach ($keys as $key) {
    redis_resp_cmd($fp, array('SET', $key, '1'));
  }

  $checked = 0;
  foreach ($patterns as $pattern) {
    $matched = redis_resp_cmd($fp, array('KEYS', $pattern));
    if (!is_array($matched)) {
      $matched = array();
    }
    $redis_set = array();
    foreach ($matched as $item) {
      $redis_set[$item] = true;
    }
    foreach ($keys as $key) {
      ++$checked;
      $redis_hit = isset($redis_set[$key]);
      $abi_hit = redis_pattern_matches_abi($pattern, $key, $signed);
      $portable_hit = redis_pattern_matches($pattern, $key);
      if ($abi_hit !== $redis_hit) {
        ++$failed;
        fwrite(STDERR, sprintf(
          "FAIL live %s ABI pattern=%s key=%s redis=%s php_abi=%s\n",
          $label,
          bin2hex($pattern),
          bin2hex($key),
          $redis_hit ? '1' : '0',
          $abi_hit ? '1' : '0'
        ));
      }
      if ($portable_hit && !$redis_hit) {
        ++$failed;
        fwrite(STDERR, sprintf(
          "FAIL live %s portable more-permissive pattern=%s key=%s\n",
          $label,
          bin2hex($pattern),
          bin2hex($key)
        ));
      }
    }
  }

  foreach (array("[a-\xC3\xA9]*", "[^a-\xC3\xA9]*") as $lock_pattern) {
    $matched = redis_resp_cmd($fp, array('KEYS', $lock_pattern));
    if (!is_array($matched)) {
      $matched = array();
    }
    $server = array('filter' => $lock_pattern);
    $locked = filter_keys_for_server($matched);
    if (in_array('banana', $locked, true)) {
      ++$failed;
      fwrite(STDERR, sprintf(
        "FAIL live %s listing leak pattern=%s still contains banana after PHP filter\n",
        $label,
        bin2hex($lock_pattern)
      ));
    }
    ++$checked;
  }

  fclose($fp);
  printf("PASS live Redis %s %s:%d (%d checks)\n", $label, $host, $port, $checked);
  return $checked;
}

$amd64_host = getenv('REDIS_AMD64_HOST') ?: '127.0.0.1';
$amd64_port = intval(getenv('REDIS_AMD64_PORT') ?: '16379', 10);
$arm64_host = getenv('REDIS_ARM64_HOST') ?: '127.0.0.1';
$arm64_port = intval(getenv('REDIS_ARM64_PORT') ?: '16381', 10);

$live_checks = 0;
$live_checks += live_redis_check('amd64-signed', $amd64_host, $amd64_port, true);
$live_checks += live_redis_check('arm64-unsigned', $arm64_host, $arm64_port, false);

if ($failed) {
  exit(1);
}

$unit = count($ascii_cases) * 3 + count($signed_cases) + count($unsigned_cases) + count($portable_cases) + 5;
printf("PASS %d Redis pattern unit checks", $unit);
if ($live_checks) {
  printf(" plus %d live Redis ABI checks", $live_checks);
}
echo "\n";
