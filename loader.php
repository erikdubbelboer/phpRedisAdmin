<?php
/**
 * Dirty copy of index.php
 *
 * @category 
 * @package
 * @subpackage
 *
 * @author Igor Malinovskiy <glide.name>
 * @file loader.php
 * @date: 08.04.13
 * @time: 15:11
 */


require_once 'includes/common.inc.php';

if ($action == null || $parentKey == null) {
    echo json_encode("Provide parameters: action & key");
    exit;
}


$keys = $redis->keys($parentKey . '*');
sort($keys);

$parentKeyParts = explode($config['seperator'], trim($parentKey));
array_pop($parentKeyParts);
$lastNamespace = array_pop($parentKeyParts);

$namespaces = array(); // Array to hold our top namespaces.
$lineNamespaces = array();

// Build an array of nested arrays containing all our namespaces and containing keys.
foreach ($keys as $key) {
    // Ignore keys that are to long (Redis supports keys that can be way to long to put in an url).
    if (strlen($key) > $config['maxkeylen']) {
        continue;
    }

    $key = explode($config['seperator'], trim($key));


    /**
     * Skip namespaces
     */
    $currLevelKey = $key[count($parentKeyParts) + 1];

    if (count($key) > 1 && in_array($currLevelKey, $lineNamespaces)) {
        continue;
    }

    // $d will be a reference to the current namespace.
    $d = &$namespaces;

    // We loop though all the namespaces for this key creating the array for each.
    // Each time updating $d to be a reference to the last namespace so we can create the next one in it.
    for ($i = 0; $i < (count($key) - 1); ++$i) {

        if (in_array($key[$i], $parentKeyParts)){
            continue;
        }

        if (!isset($d[$key[$i]])) {
            $d[$key[$i]] = array();
        }

        $d = &$d[$key[$i]];

        if (!in_array($key[$i], $lineNamespaces)) {
            $lineNamespaces[] = $key[$i];
        }
    }

    // Nodes containing an item named __phpredisadmin__ are also a key, not just a directory.
    // This means that creating an actual key named __phpredisadmin__ will make this bug.
    $d[$key[count($key) - 1]] = array('__phpredisadmin__' => true);

    // Unset $d so we don't accidentally overwrite it somewhere else.
    unset($d);
}

// Recursive function used to print the namespaces.
function print_namespace($item, $name, $fullkey, $islast, $loaded = false) {
    global $config, $server, $redis, $types;

    // Is this also a key and not just a namespace?
    if (isset($item['__phpredisadmin__'])) {
        // Unset it so we won't loop over it when printing this namespace.
        unset($item['__phpredisadmin__']);

        $type  = $types[$redis->type($fullkey)];
        $class = array();
        $len   = false;

        if (isset($_GET['key']) && ($fullkey == $_GET['key'])) {
            $class[] = 'current';
        }
        if ($islast) {
            $class[] = 'last';
        }

        // Get the number of items in the key.
        if (!isset($config['faster']) || !$config['faster']) {
            switch ($type) {
                case 'hash':
                    $len = $redis->hLen($fullkey);
                    break;

                case 'list':
                    $len = $redis->lLen($fullkey);
                    break;

                case 'set':
                    // This is currently the only way to do this, this can be slow since we need to retrieve all keys
                    $len = count($redis->sMembers($fullkey));
                    break;

                case 'zset':
                    // This is currently the only way to do this, this can be slow since we need to retrieve all keys
                    $len = count($redis->zRange($fullkey, 0, -1));
                    break;
            }
        }


        ?>
        <li<?php echo empty($class) ? '' : ' class="'.implode(' ', $class).'"'?>>
            <a href="?view&amp;s=<?php echo $server['id']?>&amp;key=<?php echo urlencode($fullkey)?>"><?php echo format_html($name)?><?php if ($len !== false) { ?><span class="info">(<?php echo $len?>)</span><?php } ?></a>
        </li>
    <?php
    }

    // Does this namespace also contain subkeys?
    if (count($item) > 0) {
        ?>
        <li class="folder<?php echo empty($fullkey) ? '' : ' collapsed'?><?php echo $islast ? ' last'
            : ''?><?php echo $loaded ? ' loaded'  : ''?>
        ">
            <div class="icon"><?php echo format_html($name)?>&nbsp;<span class="info"></span>
                <?php if (!empty($fullkey)) { ?><a href="delete.php?s=<?php echo $server['id']?>&amp;tree=<?php echo urlencode($fullkey)?>:" class="deltree"><img src="images/delete.png" width="10" height="10" title="Delete tree" alt="[X]"></a><?php } ?>
            </div><ul>
                <?php

                $l = count($item);

                foreach ($item as $childname => $childitem) {
                    // $fullkey will be empty on the first call.
                    if (empty($fullkey)) {
                        $childfullkey = $childname;
                    } else {
                        $childfullkey = $fullkey.$config['seperator'].$childname;
                    }

                    print_namespace($childitem, $childname, $childfullkey, (--$l == 0));
                }

                ?>
            </ul>
        </li>
    <?php
    }
}

$parentKeyParts[] = $lastNamespace;
$fullKey = implode(':', $parentKeyParts);

 foreach ($namespaces as $key => $item) {
     print_namespace($item, $key, $fullKey, empty($item), true);
 }






