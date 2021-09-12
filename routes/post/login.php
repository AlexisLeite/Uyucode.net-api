<?php
$_POST = $GLOBALS['input'];

return [
  'login/' => function () {
    $users = resource('users');

    $wrongCredentials = error(
      'WRONG_CREDENTIALS',
      'The provided credentials are wrong. If you are trying to login as a guest, you must provide your avatar.'
    );


    // Se inicia sesion con usuario y contraseña
    if (isset($_POST['name'], $_POST['pass'])) {
      [$name, $pass] = [$_POST['name'], $_POST['pass']];

      $user = $users->get($name);

      if ($user === null || $user['pass'] !== $pass) return $wrongCredentials;

      if (isset($_POST['remember']) && !isset($user['hash'])) {
        $user['hash'] = randomHash();
        $users->set($name, $user, true);
      }
    }

    // Se inicia sesión con usuario y hash
    else if (isset($_POST['name'], $_POST['hash'])) {
      [$name, $hash] = [$_POST['name'], $_POST['hash']];

      $user = $users->get($name);

      if ($user === null || $user['hash'] !== $hash) return $wrongCredentials;
    }

    // Se inicia sesión como invitado
    else if (isset($_POST['name'], $_POST['asGuest'], $_POST['avatar'])) {
      $user = [
        'name' => $_POST['name'],
        'avatar' => $_POST['avatar']
      ];
    }

    if (!$user) {
      return $wrongCredentials;
    }

    if (isset($user['pass'])) unset($user['pass']);
    return $user;
  }
];
