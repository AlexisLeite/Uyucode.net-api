<?php
middleware("clienter");

$input = $GLOBALS['input'];
$routes = [];

foreach ($GLOBALS['dbSchemas'] as $collection => $schema) {
  $routes["clienter/$collection/"] = function ($args) use ($collection, $input, $schema) {
    if (!is_numeric($args[0])) return error("Wrong format", "There is no id set");

    foreach (getValidators($collection) as $fieldName => $validator) {
      $res = $validator($input, ['required']);

      if ($res !== true) return error("Wrong information", $res);
    }

    // If the app reached this point, the validation has passed
    $resource = resource("clienter/$collection");

    $newRegister = array_merge($input, [
      'updatedAt' => time(),
    ]);

    $resource->update($args[0], $newRegister)->save();

    return clienterSuccess([$newRegister]);
  };
}

return $routes;
