<?php

// Init the clienter
$GLOBALS['dbSchemas'] = [
  'clients' => [
    '_id' => [
      'type' => 'number'
    ],
    'address' => [
      'type' => 'string'
    ],
    'city' => [
      'type' => 'string'
    ],
    'name' => [
      'min_length' => 5,
      'type' => 'string',
      'required' => true
    ],
    'phone' => [
      'type' => 'string',
      'required' => true,
      'min_length' => 8,
      'max_length' => 9
    ],
    'createdAt' => [
      'type' => 'time'
    ],
    'updatedAt' => [
      'type' => 'time'
    ],
  ],
  'orders' => [
    '_id' => [
      'type' => 'number'
    ],
    'client' => [
      'type' => 'number',
      'required' => true,
      'populate' => [
        'from' => 'clients',
        'key' => '_id'
      ]
    ],
    'diagnosis' => [
      'type' => 'string'
    ],
    'equip' => [
      'type' => 'string',
      'required' => true,
      'min_length' => 5
    ],
    'initialBudget' => [
      'type' => 'number',
    ],
    'totalBudget' => [
      'type' => 'virtual',
      'generator' => function ($document) {
        $budget = $document['initialBudget'];
        foreach ($document['updates'] as $update)
          $budget += $update['budget'];
        return $budget;
      }
    ],
    'status' => [
      'type' => "string"
    ],
    'updates' => [
      'type' => 'collection',
      'populate' => [
        'from' => 'updates',
        'key' => 'order'
      ]
    ],
    'createdAt' => [
      'type' => 'time'
    ],
    'updatedAt' => [
      'type' => 'time'
    ],
  ],
  'updates' => [
    '_id' => [
      'type' => 'number'
    ],
    'order' => [
      'type' => 'number',
      'required' => true,
    ],
    'description' => [
      'type' => 'string'
    ],
    'budget' => [
      'type' => 'number',
    ],
    'title' => [
      'type' => "string"
    ],
    'createdAt' => [
      'type' => 'time'
    ],
    'updatedAt' => [
      'type' => 'time'
    ],
  ]
];

$GLOBALS['dbValidators'] = null;
function makeValidators()
{
  if ($GLOBALS['dbValidators'] === null) {
    $GLOBALS['dbValidators'] = [];

    $assertions = require(__DIR__ . '/assertions.php');
    $assertionsOrder = array_keys($assertions);

    foreach ($GLOBALS['dbSchemas'] as $schemaName => $schema) {
      $GLOBALS['dbValidators'][$schemaName] = [];

      foreach ($schema as $fieldName => $fieldDefinition) {
        uksort($fieldDefinition, function ($a, $b) use ($assertionsOrder) {
          $aPos = array_search($a, $assertionsOrder);
          if ($aPos === false) $aPos = 5000;
          $bPos = array_search($b, $assertionsOrder);
          if ($bPos === false) $bPos = 5000;

          return $aPos - $bPos;
        });

        $fieldValidators = [];

        foreach ($fieldDefinition as $assertionName => $assertionArgument) {
          if (isset($assertions[$assertionName]))
            $fieldValidators[$assertionName] = $assertions[$assertionName]($fieldName, $fieldDefinition);
        }

        $GLOBALS['dbValidators'][$schemaName][$fieldName] = function ($input, $skip = []) use ($fieldName, $fieldValidators) {
          foreach ($fieldValidators as $validatorName => $validator) {
            if (in_array($validatorName, $skip)) continue;
            $res = $validator($input);
            if ($res !== true) return "The field '$fieldName' " . $res;
          }
          return true;
        };
      }
    }
  }
}

function getValidators($collection)
{
  makeValidators();

  return $GLOBALS['dbValidators'][$collection];
}

function populate($collection, $documents)
{
}
