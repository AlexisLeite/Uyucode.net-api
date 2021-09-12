<?php
middleware("clienter");

$routes = [];

foreach ($GLOBALS['dbSchemas'] as $name => $schema) {
  $routes["clienter/$name/"] = function ($args) use ($name, $schema) {
    $resource = resource("clienter/$name");
    $resource->delete($args[0])->save();

    return clienterSuccess();
  };
}

return $routes;
