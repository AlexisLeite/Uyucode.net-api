<?php

$a = [
  'messages' => [
    ['from' => 'pepe']
  ]
];
$b = [
  'messages' => [
    ['from' => 'mario']
  ]
];

print_r(array_merge_recursive($a, $b));
exit;
