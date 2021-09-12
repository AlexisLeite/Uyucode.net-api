<?php

$GLOBALS['expressions'] = [
  'string' => "/.*/",
  'number' => "/^\d*$/",
  'time' => "/^\d+$/"
];

/**
 * The assertions file will be used to check for every input coming into the server
 * 
 * Each index of the return array is a definition field which can be used to set a different need in each input field. 
 * 
 * The general validation will only pass if all the fields pass its own validation.
 */

return [
  'required' => function ($fieldName, $fieldDefinition) {
    return function ($input) use ($fieldName) {
      if (isset($input[$fieldName])) return true;

      return "is required.";
    };
  },
  'type' => function ($fieldName, $fieldDefinition) {
    return function ($input) use ($fieldName, $fieldDefinition) {
      if (
        !isset($input[$fieldName])
        || preg_match($GLOBALS['expressions'][$fieldDefinition['type']], $input[$fieldName])
      )
        return true;
      else
        return "must be an '{$fieldDefinition['type']}'.";
    };
  },
  'min_length' => function ($fieldName, $fieldDefinition) {
    // The min length will only check the value if isset and if it's an string. If you must check whenever it's a string or not you should use the 'type' validation.
    return function ($input) use ($fieldDefinition, $fieldName) {
      if (
        !isset($input[$fieldName])
        || !is_string($input[$fieldName])
        || strlen($input[$fieldName]) >= $fieldDefinition['min_length']
      ) {
        return true;
      }
      return "must have at least {$fieldDefinition['min_length']} characters.";
    };
  },
  'max_length' => function ($fieldName, $fieldDefinition) {
    return function ($input) use ($fieldDefinition, $fieldName) {
      if (
        !isset($input[$fieldName])
        || !is_string($input[$fieldName])
        || strlen($input[$fieldName]) <= $fieldDefinition['max_length']
      ) {
        return true;
      }
      return "must have at most {$fieldDefinition['max_length']} characters.";
    };
  }
];
