<?php
middleware("clienter");

$input = $GLOBALS['input'];
$routes = [];

foreach ($GLOBALS['dbSchemas'] as $collection => $schema) {
  $routes["clienter/$collection"] = function () use ($collection, $input, $schema) {
    foreach (getValidators($collection) as $fieldName => $validator) {
      $res = $validator($input);

      if ($res !== true) return error("Wrong information", $res);
    }

    // If the app reached this point, the validation has passed
    $resource = resource("clienter/$collection");

    $newRegister = array_merge($input, [
      'createdAt' => time(),
      'updatedAt' => time(),
      '_id' => $resource->nextKey()
    ]);

    $resource->add(null, $newRegister)->save();

    return clienterSuccess([$newRegister]);
  };
}

return $routes;
