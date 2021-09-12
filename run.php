<?php
// Method in charge of defining which is the route to apply
function run()
{
  if (!isset($_GET['route']))
    $route = '';
  else
    $route = $_GET['route'];

  if (!isset($GLOBALS['routes'][$GLOBALS['method']])) {
    return error404();
  }

  $routes = $GLOBALS['routes'][$GLOBALS['method']];

  $foundRoute = ['length' => 0];
  foreach ($routes as $searchRoute => $callback) {
    if (strpos($route, $searchRoute) === 0) {
      preg_match("&^{$searchRoute}(.+)?$&", $route, $matches);
      if (strlen($searchRoute) > $foundRoute['length']) {
        $arguments = isset($matches[1]) ? explode('/', $matches[1]) : [];
        $foundRoute = [
          'arguments' => $arguments,
          'callback' => $callback,
          'length' => strlen($searchRoute),
          'path' => $searchRoute,
        ];
      }
    }
  }

  if ($foundRoute['length'] > 0) {
    return $foundRoute['callback']($foundRoute['arguments'], $foundRoute);
  } else {
    return error404();
  }
}
