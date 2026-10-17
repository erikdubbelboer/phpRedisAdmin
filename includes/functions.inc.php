<?php

function format_html($str) {
  global $server;

  if (isset($server['charset']) && $server['charset']) {
    $res = mb_convert_encoding($str, 'utf-8', $server['charset']);
  } else {
    $res = $str;
  }

  $res = htmlentities($res, defined('ENT_SUBSTITUTE') ? (ENT_QUOTES | ENT_SUBSTITUTE) : ENT_QUOTES, 'utf-8');

  return ($res || !$str) ? $res :  '(' . strlen($str) . ' bytes)';
}


function input_convert($str) {
  global $server;

  if (isset($server['charset']) && $server['charset']) {
    return mb_convert_encoding($str, $server['charset'], 'utf-8');
  } else {
    return $str;
  }
}


// This matcher is adapted from Redis stringmatchlen() in src/util.c.
// See LICENSE.redis-stringmatch for the source copyright and license.
//
// Redis class-range bytes follow the host C char signedness:
// amd64 Linux signed char, arm64 AAPCS unsigned char. PHP ord() is always
// unsigned. A key is in-filter only when both ABIs match (fail closed).
// [a-\xC3\xA9]* excludes banana on signed Redis; [^a-\xC3\xA9]* excludes
// banana on unsigned Redis.
function redis_pattern_range_byte($character, $signed) {
  $value = ord($character);
  if ($signed && $value >= 128) {
    return $value - 256;
  }
  return $value;
}


function redis_pattern_matches_impl($pattern, $patternOffset, $patternLength, $string, $stringOffset, $stringLength, &$skipLongerMatches, $nesting, $signed) {
  if ($nesting > 1000) {
    return false;
  }

  while ($patternLength && $stringLength) {
    switch ($pattern[$patternOffset]) {
      case '*':
        while ($patternLength > 1 && $pattern[$patternOffset + 1] === '*') {
          ++$patternOffset;
          --$patternLength;
        }

        if ($patternLength === 1) {
          return true;
        }

        while ($stringLength) {
          if (redis_pattern_matches_impl($pattern, $patternOffset + 1, $patternLength - 1, $string, $stringOffset, $stringLength, $skipLongerMatches, $nesting + 1, $signed)) {
            return true;
          }

          if ($skipLongerMatches) {
            return false;
          }

          ++$stringOffset;
          --$stringLength;
        }

        $skipLongerMatches = true;
        return false;

      case '?':
        ++$stringOffset;
        --$stringLength;
        break;

      case '[':
        ++$patternOffset;
        --$patternLength;

        $not = $patternLength && $pattern[$patternOffset] === '^';
        if ($not) {
          ++$patternOffset;
          --$patternLength;
        }

        $match = false;
        while (true) {
          if ($patternLength >= 2 && $pattern[$patternOffset] === '\\') {
            ++$patternOffset;
            --$patternLength;
            if ($pattern[$patternOffset] === $string[$stringOffset]) {
              $match = true;
            }
          } elseif ($patternLength && $pattern[$patternOffset] === ']') {
            break;
          } elseif ($patternLength === 0) {
            --$patternOffset;
            ++$patternLength;
            break;
          } elseif ($patternLength >= 3 && $pattern[$patternOffset + 1] === '-') {
            $start = redis_pattern_range_byte($pattern[$patternOffset], $signed);
            $end = redis_pattern_range_byte($pattern[$patternOffset + 2], $signed);
            $character = redis_pattern_range_byte($string[$stringOffset], $signed);

            if ($start > $end) {
              $temporary = $start;
              $start = $end;
              $end = $temporary;
            }

            $patternOffset += 2;
            $patternLength -= 2;
            if ($character >= $start && $character <= $end) {
              $match = true;
            }
          } elseif ($pattern[$patternOffset] === $string[$stringOffset]) {
            $match = true;
          }

          ++$patternOffset;
          --$patternLength;
        }

        if ($not) {
          $match = !$match;
        }
        if (!$match) {
          return false;
        }

        ++$stringOffset;
        --$stringLength;
        break;

      case '\\':
        if ($patternLength >= 2) {
          ++$patternOffset;
          --$patternLength;
        }
        // Fall through and compare the escaped byte.
      default:
        if ($pattern[$patternOffset] !== $string[$stringOffset]) {
          return false;
        }

        ++$stringOffset;
        --$stringLength;
        break;
    }

    ++$patternOffset;
    --$patternLength;

    if ($stringLength === 0) {
      while ($patternLength && $pattern[$patternOffset] === '*') {
        ++$patternOffset;
        --$patternLength;
      }
      break;
    }
  }

  return $patternLength === 0 && $stringLength === 0;
}


function redis_pattern_matches_abi($pattern, $string, $signed) {
  $skipLongerMatches = false;

  return redis_pattern_matches_impl(
    $pattern,
    0,
    strlen($pattern),
    $string,
    0,
    strlen($string),
    $skipLongerMatches,
    0,
    $signed
  );
}


function redis_pattern_matches($pattern, $string) {
  return redis_pattern_matches_abi($pattern, $string, true)
    && redis_pattern_matches_abi($pattern, $string, false);
}


function key_matches_server_filter($key) {
  global $server;

  $filter = isset($server['filter']) ? $server['filter'] : '*';
  return $filter === '*' || redis_pattern_matches($filter, $key);
}


function require_key_in_server_filter($key) {
  if (!key_matches_server_filter($key)) {
    http_response_code(403);
    die('Key is outside the configured filter for this server.');
  }
}


function filter_keys_for_server($keys) {
  return array_values(array_filter($keys, 'key_matches_server_filter'));
}


function format_time($time) {
  $minute = 60;
  $hour   = $minute * 60;
  $day    = $hour   * 24;

  $when = $time;

  if ($when > $day) {
    $tmpday = floor($when / $day);
    $tmphour = floor(($when / $hour) - (24*$tmpday));
    $tmpminute = floor(($when / $minute) - (24*60*$tmpday) - ($tmphour * 60));
    $tmpsec = floor($when - (24*60*60*$tmpday) - ($tmphour * 60 * 60) - ($tmpminute * 60));
    return sprintf("%d day%s %d hour%s %d min%s %d sec%s",$tmpday,($tmpday != 1) ? 's' : '',$tmphour,($tmphour != 1) ? 's' : '',$tmpminute,($tmpminute != 1) ? 's' : '',$tmpsec,($tmpsec != 1) ? 's' : '');
  } else if ($when > $hour) {
    $tmphour = floor($when / $hour);
    $tmpminute = floor(($when / $minute) - ($tmphour * 60));
    $tmpsec = floor($when - ($tmphour * 60 * 60) - ($tmpminute * 60));
    return sprintf("%d hour%s %d min%s %d sec%s",$tmphour,($tmphour != 1) ? 's' : '',$tmpminute,($tmpminute != 1) ? 's' : '',$tmpsec,($tmpsec != 1) ? 's' : '');
  } else if ($when > $minute) {
    $tmpminute = floor($when / $minute);
    $tmpsec = floor($when - ($tmpminute * 60));
    return sprintf("%d min%s %d sec%s",$tmpminute,($tmpminute != 1) ? 's' : '',$tmpsec,($tmpsec != 1) ? 's' : '');
  } else {
    return sprintf("%d sec%s",$when,($when != 1) ? 's' : '');
  }
}


function format_size($size) {
  $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

  if ($size == 0) {
    return '0 B';
  } else {
    return round($size / pow(1024, ($i = floor(log($size, 1024)))), 1).' '.$sizes[$i];
  }
}


function format_ttl($seconds) {
	if ($seconds > 60) {
		return sprintf('%d (%s)', $seconds, format_time($seconds));
	} else {
		return $seconds;
	}
}


function str_rand($length) {
  $r = '';

  for (; $length > 0; --$length) {
    $r .= chr(rand(32, 126)); // 32 - 126 is the printable ascii range
  }

  return $r;
}


function encodeOrDecode($action, $key, $data) {
  global $server;

  if (isset($_GET['raw']) || !isset($server['serialization'])) {
    return $data;
  }

  foreach ($server['serialization'] as $pattern => $closures) {
    if (fnmatch($pattern, $key)) {
      return $closures[$action]($data);
    }
  }

  return $data;
}

function getRelativePath($base) {
  return substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], $base));
}
