<?php
$_POST = $GLOBALS['input'];

return ['register/' => function () {
  // Not enough data provided
  if (!isset($_POST['name'], $_POST['pass'], $_POST['image']))
    return error('WRONG_PARAMETERS', 'In order to register you must provide a name, a password and an image');

  [$name, $pass, $image] = [$_POST['name'], $_POST['pass'], $_POST['image']];

  $users = resource('users');

  // The user has already been chosen
  if ($users->exists[$name])
    return error('ALREADY_EXISTENT_USER', 'The provided user has already been taken.');

  // Can register the user
  $users->set($name, [
    'name' => $name,
    'pass' => $pass,
    'image' => $image,
    'score' => 0,
    'hash' => randomHash()
  ], true);

  $user = $users->get($name);
  unset($user['pass']);
  return $user;
}];
