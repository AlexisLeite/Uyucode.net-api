<?php

return [
  'tttMap/' => function ($args = null) {
    $translates = json_decode(file_get_contents(baseDir() . '/resources/pathsTranslationMap.json'), true);

    if (isset($args[0]) && strlen($args[0]) > 0) {
      $route = explode('.', $args[0]);

      if (sizeof($route) === 1) {
        $file = baseDir() . "/resources/tttMaps/{$route[0]}.json";
        unset($route[0]);
      } else if (sizeof($route)) {
        $file = baseDir() . "/resources/tttMaps/{$route[0]}.{$route[1]}.json";
        unset($route[0]);
        unset($route[1]);
      }
    } else
      $file = baseDir() . '/resources/pathsTranslate.json';

    $paths = json_decode(file_get_contents($file), true);
    if (sizeof($args)) {

      // Find the current path
      while (sizeof($route)) {
        $paths = $paths[$translates['moves']][array_shift($route)];
      }
    }

    if (isset($paths[$translates['moves']]))
      foreach ($paths[$translates['moves']] as $key => $v)
        unset($paths[$translates['moves']][$key][$translates['moves']]);

    $search = array_map(function ($val) {
      return "\"$val\"";
    }, array_values($translates));
    $replace = array_map(function ($val) {
      return "\"$val\"";
    }, array_keys($translates));
    if (sizeof($args) && (sizeof(explode('.', $args[0]))) & 1) {
      $replaceString = json_encode($replace);
      $replaceString = str_replace(
        ['winner', 'loser'],
        ['plsr', 'pwnr'],
        $replaceString
      );
      $replace = json_decode(str_replace(
        ["plsr", "pwnr"],
        ["loser", "winner"],
        $replaceString
      ));
    }
    $translated = str_replace($search, $replace, json_encode($paths));
    return $translated;
  },
  // Update map file
  /* 'tttMap/update/' => function ($args) {
include(baseDir() . '/resources/tttTree.php');
$start = [0 => null, 1 => null, 2 => null, 4 => null];
$pathsTree = new tttTree([null, null, null, null, null, null, null, null, null], false, $start);
$content = json_encode($pathsTree);
file_put_contents(baseDir() . '/resources/paths.json', $content);
return ['message' => strlen($content) . "bytes writen"];
}, */
  // Translate map file
  /* 'tttTranslate' => function () {
$file = fopen(baseDir() . '/resources/paths.json', 'r');
$translateFile = fopen(baseDir() . '/resources/pathsTranslate.json', 'w');
$doneTranslates = [];
$lastKey = 'A';

do {
$read = fread($file, 8096);
while (preg_match("/[a-zA-Z_-]$/", $read) && !feof($file))
$read .= fread($file, 20);


$translated = preg_replace_callback("/([a-zA-Z_-]+)/", function ($match) use (&$doneTranslates, &$lastKey) {
if (in_array($match[1], ['false', 'true'])) return $match[1];
if (!array_key_exists($match[1], $doneTranslates)) {
// Assign with the last generated key
$doneTranslates[$match[1]] = $lastKey;

// Increment the last key in order to gen a new unique one
$length = strlen($lastKey) - 1;
$lastKey[$length] = chr(ord($lastKey[$length]) + 1);
if (ord($lastKey[$length]) > ord('Z') && ord($lastKey[$length]) < ord('a')) { $lastKey[$length]="a" ; } if (ord($lastKey[$length])> ord('z')) {
  $lastKey[$length] = "z";
  $lastKey .= 'A';
  }
  }

  return $doneTranslates[$match[1]];
  }, $read);

  fwrite($translateFile, $translated);
  } while (!feof($file));
  fclose($file);
  fclose($translateFile);
  file_put_contents(baseDir() . '/resources/pathsTranslationMap.json', json_encode($doneTranslates));
  }, */
];
