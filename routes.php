<?php
function loadDirectory($directory)
{
  $foundRoutes = [];

  $routeFiles = array_filter(scandir($directory), function ($fileName) {
    // Filter the top and current links
    return !in_array($fileName, ['.', '..']);
  });

  foreach ($routeFiles as $file) {
    $foundRoutes = array_merge($foundRoutes, include("$directory/$file"));
  }

  return $foundRoutes;
}

$routes = [
  'GET' => loadDirectory(__DIR__ . '/routes/get'),
  'POST' => loadDirectory(__DIR__ . '/routes/post'),
  'PATCH' => loadDirectory(__DIR__ . '/routes/patch'),
  'DELETE' => loadDirectory(__DIR__ . '/routes/delete')
];
