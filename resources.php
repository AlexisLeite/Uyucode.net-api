<?php

define('enableAutoincrement', 'enableAutoincrement');
define('nextKey', 'nextKey');
define('lastInsertedKey', 'lastInsertedKey');
define('metadata', '__metadata');

class Resource
{
  public $lastInsertedKey = null;

  private $metaData;

  public function __construct($uri, $options = [])
  {
    $this->baseDir = baseDir('resources');
    $this->options = array_merge([
      'makeit' => true,
      enableAutoincrement => true
    ], $options);

    $this->resource = "{$this->baseDir}/{$uri}.json";

    $initialMetaData = [nextKey => 0, lastInsertedKey => null];

    if (!file_exists($this->resource)) {
      createResource($uri, [metadata => $initialMetaData]);
    }

    try {
      $this->resourceFile = fopen($this->resource, "a+");
      if (flock($this->resourceFile, LOCK_EX)) {
        // do your file writes here
        $rawData = fread($this->resourceFile, filesize($this->resource));
        $this->data = json_decode($rawData, true);
        if (isset($this->data[metadata])) {
          $this->metaData = $this->data[metadata];
          $this->hasMetaData = true;
          unset($this->data[metadata]);
        } else {
          $this->hasMetaData = false;
          $this->metadata = $initialMetaData;
        }

        if ($this->hasMetaData === null) $this->options[enableAutoincrement] = false;
      }
    } catch (Exception $e) {
      throw new Exception('Cant load the resource', 0, $e);
    }
  }

  public function __destruct()
  {
    flock($this->resourceFile, LOCK_UN);
    fclose($this->resourceFile);
  }

  public function save()
  {
    try {
      ftruncate($this->resourceFile, 0);
      $data = $this->data;
      if ($this->hasMetaData) $data[metadata] = $this->metaData;
      fwrite($this->resourceFile, json_encode($data));
      return true;
    } catch (Exception $e) {
      throw new Exception('Cant save the resource', 0, $e);
    }
  }

  // Data access & modification
  public function all()
  {
    $allData = $this->data;
    return $allData;
  }

  public function each($callback)
  {
    if (is_callable(($callback)))
      foreach ($this->data as $key => $value)
        $callback($value, $key);

    return $this;
  }

  public function get($key)
  {
    if ($this->exists($key)) return $this->data[$key];
    else return null;
  }

  public function empty()
  {
    $this->data = [];
    return $this;
  }

  public function exists($key)
  {
    return isset($this->data[$key]);
  }

  public function filter($filter)
  {
    $this->data = array_filter($this->data, $filter, ARRAY_FILTER_USE_BOTH);
    return $this;
  }

  public function lastInsertedKey()
  {
    return $this->metaData[lastInsertedKey];
  }

  public function nextKey()
  {
    if (isset($this->metaData[nextKey])) return $this->metaData[nextKey];
    return null;
  }

  // The store must be of type $key => {Object}, then it will search through objects to find the mad key
  public function max($key)
  {
    $maxFound = null;
    $maxFoundIndex = null;
    foreach ($this->data as $index => $el) {
      if ($el[$key] > $maxFound) {
        $maxFound = $el[$key];
        $maxFoundIndex = $index;
      }
    }

    return $maxFoundIndex;
  }
  public function min($key)
  {
    $minFound = null;
    $minFoundIndex = null;
    foreach ($this->data as $index => $el) {
      if ($el[$key] < $minFound) {
        $minFound = $el[$key];
        $minFoundIndex = $index;
      }
    }

    return $minFoundIndex;
  }

  public function put($key, $data)
  {
    if (!$this->exists($key))
      $this->data[$key] = [];

    $this->data[$key] = array_merge_recursive($this->data[$key], $data);
    return $this;
  }

  // delete and remove will be aliases
  public function delete($key, $save = false)
  {
    return $this->remove($key, $save);
  }
  public function remove($key, $save = false)
  {
    if (isset($this->data[$key])) {
      unset($this->data[$key]);
      if ($save) $this->save();
    }
    return $this;
  }

  // add and set are aliases
  public function add($key, $value)
  {
    return $this->set($key, $value);
  }
  public function set($key, $value)
  {
    // Through this options it is possible to define an autoincrement id
    if ($key === null) {
      $lastKey = $this->metaData[lastInsertedKey] = $this->metaData[nextKey]++;
      if ($this->options[enableAutoincrement]) {
        $this->data[$lastKey] = array_merge($value, ['id' => $lastKey]);
      } else {
        $this->data[$lastKey] = $value;
      }
    } else {
      $this->metaData[lastInsertedKey] = $key;
      if (!isset($this->data[$key]) && $this->options[enableAutoincrement]) {
        $this->data[$key] = array_merge($value, ['id' => $this->metaData[nextKey]++]);
      } else {
        $this->data[$key] = $value;
      }
    }

    return $this;
  }

  public function sort($key = null)
  {
    if ($key === null)
      ksort($this->data);
    else
      usort($this->data, function ($a, $b) use ($key) {
        $a = $a[$key];
        $b = $b[$key];
        return $a === $b ? 0 : ($a < $b ? -1 : 1);
      });

    return $this;
  }

  // The same as put, but the arrays will be replaced
  public function update($key, $data = [])
  {
    if (!$this->exists($key))
      $this->data[$key] = [];

    $this->data[$key] = array_merge($this->data[$key], $data);
    return $this;
  }
}

$loadedResources = [];
function resource($name)
{
  if (!array_key_exists($name, $GLOBALS['loadedResources']))
    $GLOBALS['loadedResources'][$name] = new Resource($name);

  return $GLOBALS['loadedResources'][$name];
}

function createResource($name, $initialData)
{
  $route = explode('/', $name);
  $name = array_pop($route);

  // Check the existence of the directory
  if (count($route)) {
    $currentDir = baseDir("resources");
    foreach ($route as $dir) {
      $currentDir .= "/$dir";
      if (!is_dir($currentDir))
        mkdir($currentDir);
    }
    $name = implode('/', $route) . "/$name";
  }
  $fileName = baseDir("resources/{$name}.json");

  if (!file_exists($fileName)) {
    file_put_contents($fileName, json_encode($initialData));
    return true;
  }
  return false;
}
