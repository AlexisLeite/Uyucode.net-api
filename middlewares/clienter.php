<?php
require_once(__DIR__ . '/clienter/schemas.php');

function clienterSuccess($documents = null, $extra = null)
{
  $returnObject = [
    'status' => 'success'
  ];

  if ($documents !== null) {
    $returnObject = array_merge($returnObject, ['results' => array_values($documents)]);
  }

  if (is_array($extra))
    $returnObject = array_merge($returnObject, $extra);

  return $returnObject;
}
