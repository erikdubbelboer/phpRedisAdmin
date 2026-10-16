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
  array('foo**bar', 'foo-value-baz', false, 'suffix remains required'),

  // Signed-byte class-range regression (wire-verified against Redis 7.0.12).
  // [a-\xC3]: a=97, \xC3=-61 signed; start>end, swap -> [-61,97].
  // 'b'=98 > 97 (excluded), '\xC3'=-61 (included), '\xFF'=-1 (included), '\x80'=-128<-61 (excluded).
  array("[a-\xC3]*", 'banana', false, 'signed-byte: high-byte endpoint folds range, b=98 excluded'),
  array("[a-\xC3]*", "\xFF",   true,  'signed-byte: \\xFF=-1 falls inside folded range [-61,97]'),
  array("[a-\xC3]*", "\x80",   false, 'signed-byte: \\x80=-128 below folded range start -61'),
  array("[a-\xC3]*", "\xC3",   true,  'signed-byte: \\xC3=-61 is range boundary, included'),
  // [^a-\xC3]*: negation - b=98 not in [-61,97], so banana is included.
  array("[^a-\xC3]*", 'banana', true, 'signed-byte: negated folded range includes b=98'),
  // [\x7F-\x80]: \x7F=127, \x80=-128 signed; swap -> [-128,127]; all low-ASCII included.
  array("[\x7F-\x80]*", 'b',    true,  'signed-byte: straddle-point range [-128,127] includes b=98'),
  array("[\x7F-\x80]*", "\xFF", true,  'signed-byte: straddle-point range [-128,127] includes \\xFF=-1'),
  // [\x80-\xFF]: \x80=-128, \xFF=-1 signed; no swap; range [-128,-1]; low-ASCII excluded.
  array("[\x80-\xFF]*", 'b',    false, 'signed-byte: all-high-byte range excludes low-ASCII b'),
  array("[\x80-\xFF]*", "\x80", true,  'signed-byte: all-high-byte range includes \\x80'),
  array("[\x80-\xFF]*", "\xFF", true,  'signed-byte: all-high-byte range includes \\xFF'),
  // Escaped class member must not initiate a range (wire parity: backslash consumes the byte literally).
  // '[\\a-z]' = bytes [ \ a - z ] : escaped 'a' is literal, no a-z range formed.
  array('[\\a-z]', 'm', false, 'escaped class member does not create a-z range'),
  array('[\\a-z]', 'a', true,  'escaped class member matches its literal byte'),
  array('[\\a-z]', 'q', false, 'escaped class member: mid-alphabet q not matched (no a-z range formed)'),

  // Maintainer-reported exact UTF-8 case: [a-é]* where é = U+00E9 = UTF-8 bytes \xC3\xA9.
  // Pattern bytes: [ a - \xC3 \xA9 ] *
  // Redis class parse: range a(97) to \xC3(-61 signed); swap -> [-61,97]; then \xA9 as literal.
  // b=98 > 97 -> not in range; \xA9 != b -> false.
  array("[a-\xC3\xA9]*", 'banana', false, 'maintainer case: [a-e-acute]* excludes banana (signed range [-61,97])'),
  // Positive: \xA9 is a literal class member after the range and should match.
  array("[a-\xC3\xA9]*", "\xA9",   true,  'maintainer case: literal \\xA9 class member matches \\xA9 key')
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
// +1 for the key_matches_server_filter functional check above.
