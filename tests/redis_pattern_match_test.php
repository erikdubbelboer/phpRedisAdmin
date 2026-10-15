<?php

// FINDING_TAG: PRA-L3-FILT-CLIENT-OVERRIDE

require_once dirname(__DIR__) . '/includes/functions.inc.php';

$cases = array(
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
  array('foo**bar', 'foo-value-baz', false, 'suffix remains required')
);

$failed = 0;

foreach ($cases as $case) {
  list($pattern, $key, $expected, $description) = $case;
  $actual = redis_pattern_matches($pattern, $key);

  if ($actual !== $expected) {
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
}

$server = array('filter' => '[!a]*');
if (!key_matches_server_filter('apple') || !key_matches_server_filter('!key') || key_matches_server_filter('banana')) {
  ++$failed;
  fwrite(STDERR, "FAIL key_matches_server_filter does not use Redis pattern semantics\n");
}

if ($failed) {
  exit(1);
}

printf("PASS %d Redis pattern regression cases\n", count($cases) + 1);
