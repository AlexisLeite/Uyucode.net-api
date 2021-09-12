<?php
middleware("clienter");

$routes = [];

foreach ($GLOBALS['dbSchemas'] as $name => $schema) {
  $routes["clienter/$name/getDetails/"] = function () use ($schema) {
    return array_keys($schema);
  };

  $routes["clienter/$name/"] = function ($args, $route) use ($name) {
    // Get the documents for the route
    $resource = resource("clienter/$name");

    $documents = [];
    if (isset($args[0]) && is_numeric($args[0])) {
      $documents[] = $resource->get($args[0]);
    } else {
      $documents = $resource->all();
    }

    // Populate the documents
    foreach ($GLOBALS['dbSchemas'][$name] as $fieldName => $definition) {
      if (isset($definition['populate'])) {
        $targetResource = resource("clienter/" . $definition['populate']['from']);
        foreach ($documents as $key => $document) {
          if ($definition['type'] === "number") {
            $res = array_filter($targetResource->all(), function ($val) use ($definition, $document, $fieldName) {
              return intval($document[$fieldName]) === intval($val[$definition['populate']['key']]);
            });
            $documents[$key][$fieldName] = array_shift($res);
          }
          if ($definition['type'] === "collection") {
            $documents[$key][$fieldName] = array_values(array_filter($targetResource->all(), function ($val) use ($document, $definition) {
              return $val[$definition['populate']['key']] === $document['_id'];
            }));
          }
        }
      }
    }

    // Make the virtuals
    foreach ($GLOBALS['dbSchemas'][$name] as $fieldName => $definition) {
      if (isset($definition['generator'])) {
        foreach ($documents as $key => $document) {
          $documents[$key][$fieldName] = $definition['generator']($document);
        }
      }
    }

    // Make the search
    function find($obj, $q)
    {
      if (is_string($obj)) {
        return preg_match("/.*$q.*/i", $obj);
      }
      if (is_array($obj)) {
        foreach ($obj as $val) {
          $res = find($val, $q);
          if ($res === 1 || $res === true) return true;
        }
      }
      return false;
    }
    if (isset($_GET['q'])) {
      $documents = array_filter($documents, function ($val) {
        return find($val, $_GET['q']);
      });
    }

    // Sort
    if (isset($_GET['sort'])) {
      $sortArguments = array_map(function ($val) {
        $val = explode('-', $val);
        $order = $val[0] === "" ? "desc" : "asc";
        if ($val[0] === "") array_shift($val);
        $val = implode("-", $val);

        return ['field' => $val, 'order' => $order];
      }, explode(',', $_GET['sort']));
      usort($documents, function ($a, $b) use ($sortArguments) {
        foreach ($sortArguments as $argument) {
          if ($a[$argument['field']] === $b[$argument['field']]) continue;
          if ($argument['order'] === "asc") {
            return (strcasecmp($a[$argument['field']], $b[$argument['field']]));
          } else {
            return (strcasecmp($b[$argument['field']], $a[$argument['field']]));
          }
        }
        return 0;
      });
    }

    // Make the pagination
    $totalDocuments = sizeof($documents);
    $extra = ['statistics' => [
      'total' => $totalDocuments
    ]];

    if (isset($_GET['perPage'])) {
      $perPage = isset($_GET['perPage']) ? $_GET['perPage'] : 10;
      $page = isset($_GET['page']) ? $_GET['page'] : 0;
      $iStart = $page * $perPage;
      $documents = array_slice($documents, $iStart, $perPage);

      $commonQueryArray = [];
      if (isset($_GET['q'])) $commonQueryArray['q'] = $_GET['q'];
      if (isset($_GET['sort'])) $commonQueryArray['sort'] = $_GET['sort'];
      if (isset($_GET['perPage'])) $commonQueryArray['perPage'] = $_GET['perPage'];

      if ($page > 0) {
        $extra['statistics']['first'] = "/$name?" . http_build_query(array_merge($commonQueryArray, [
          'page' => 0,
        ]));
        $extra['statistics']['prev'] = "/$name?" . http_build_query(array_merge($commonQueryArray, [
          'page' => $page - 1,
        ]));
      }
      if (($iStart + $perPage) < $totalDocuments) {
        $extra['statistics']['last'] = "/$name?" . http_build_query(array_merge($commonQueryArray, [
          'page' => ceil(($totalDocuments / $perPage) - 1),
        ]));
        $extra['statistics']['next'] = "/$name?" . http_build_query(array_merge($commonQueryArray, [
          'page' => $page + 1,
        ]));
      }
    }

    return clienterSuccess($documents, $extra);
  };
}

return $routes;
