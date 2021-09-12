<?php

/**
 * # Utils
 * 
 * The utils library brings some util methods, used along the micro json api. Those methods are explained below.
 */
// Set the current hostname
$hostActual = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];

preg_match('/(.*)(?::(.*))?/', $hostActual, $hostInfo);

// Set the protocol
$hostName = $hostInfo[1];
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://';

$baseUri = "$protocolo$hostName";

// Set current method and inputs
$GLOBALS['method'] = strtoupper($_SERVER['REQUEST_METHOD']);

if (in_array($GLOBALS['method'], ['PUT', 'POST', 'PATCH']))
  $GLOBALS['input'] = json_decode(file_get_contents('php://input'), true);
else
  $GLOBALS['input'] = $_GET;

/**
 * It returns an absolute path to the $route specified. It's very util to require files when you are in a nested directory.
 * 
 * @param $route :optional If is set, the returned route will be the specified but relative to the root of the project. 
 * 
 * @return string The route you were looking for.
 */
function baseDir($route = null)
{
  if ($route !== null) return __DIR__ . "/$route";
  return __DIR__;
}

/**
 * This method allows to search deep in an associative array to find if a route exists.
 * 
 * @param array obj the object within you want to search.
 * 
 * @param string route the string used as the path to follow for checking the existence.
 * 
 * @example
 * 
 * ```php
 * $user = [
 *  'personalInfo' => [
 *    'name' => [ 
 *      'first' => 'Alexis',
 *      'last' => 'Leite',
 *    ],
 *    'birthday' => [
 *      'month' => 3,
 *      'day' => 1
 *    ]
 *  ],
 *  'registrationData' => [...]
 * ];
 * 
 * $name = exists($userData, 'personalInfo.name.first');
 * if($name) echo "Hello $name"; // Echoes Hello Alexis
 * 
 * $street = exists($userData, 'personalInfo.adress.street');
 * if($adress) echo "You live in the street $street"; // Does not echo anything
 * ```
 * 
 * @return null if the object does not have the passed route. The value at the route if it exists.
 */
function exists($obj, $route)
{
  $route = explode('.', $route);
  foreach ($route as $step) {
    if (isset($obj[$step]))
      $obj = $obj[$step];
    else
      return null;
  }
  return $obj;
}

/**
 * Anytime the api must exit, it's preferred to do so with an json object representing the error.
 * 
 * @param any $error an error object, can be a string or anything that can be parsed to json
 * 
 * @param any $message the same as $error
 * 
 * @return object ['error' => $error, 'message' => $message, 'backtrace' => debug_backgrace(), 'route' => 'The query route of the request'];
 */
function error($error, $message)
{
  return ['status' => 'error', 'error' => $error, 'message' => $message, 'data' => [ /* 'backtrace' => debug_backtrace() */], 'route' => exists($_GET, 'route')];
}

function error404()
{
  return error("Not found", "The provided route got no results");
}

/**
 * This method returns the list of files within a directory and offers the possibility to filter wether you want to get the directories or not, or if you want to get them classified or not.
 * 
 * @param string $dir the route to the dir you want to inspect
 * 
 * @param bool $onlyFiles when true, it will return the files only. When false, it will include the directories too.
 * 
 * @param bool $classify if it's set to true, this method will return an object with two properties: files and dirs, each of those are an array of file and directory names.
 * 
 *  @return array|object the list of files within the directory. 
 */
function fileList($dir, $onlyFiles = true, $classify = null)
{
  $files = scandir($dir);
  $files = array_filter($files, function ($file) use ($dir, $onlyFiles) {
    return !in_array($file, ['.', '..']) && (!$onlyFiles || ($onlyFiles && is_file("$dir/$file")));
  });

  if ($classify === null && $onlyFiles === false) $classify = true;

  if ($classify) {
    $classifiedFiles = [];
    $dirs = [];
    foreach ($files as $file)
      if (is_file("$dir/$file")) $classifiedFiles[] = $file;
      else $dirs[] = $file;

    return (object)['files' => $classifiedFiles, 'dirs' => $dirs];
  }
  return $files;
}

function randomHash()
{
  return bin2hex(random_bytes(16));
}

/**
 * This method accepts a relative path in form of string and returns an absolute url to that relative path. To do so, it will inspect the current protocol and domain.
 * 
 * @param string $sufix the relative path
 * 
 * @return the url
 */
function url($sufix)
{
  return $GLOBALS['baseUri'] . '/' . $sufix;
}

/**
 * This method accepts a middleware name and loads it, if it wasn't yet loaded
 */
function middleware($name)
{
  $name = baseDir("middlewares/$name.php");
  if (file_exists($name))
    require_once($name);
}
