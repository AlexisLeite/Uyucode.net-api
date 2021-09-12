<?php
return [
  'avatars/' => function ($arguments = []) {
    return array_map(function ($el) {
      return url("/images/$el");
    }, fileList(__DIR__ . '/../../../images'));
  },
  'chat/' => function ($args) {
  },
  'debug/' => function () {
    header('Content-type: text/html');
    include(baseDir("debugs/index.php"));
    die;
  },
  'document/' => function ($arguments) {
    $route = implode('/', array_filter($arguments));
    $file = file_get_contents(baseDir($route));

    preg_match_all("/((?:public)|(?:private)) function ([^\(]+)(.*)/", $file, $functions, PREG_SET_ORDER);
    foreach ($functions as $function) {
      echo "\n### {$function[1]} {$function[2]} {$function[3]}\n";
      preg_match("/\((.*)\)/", $function[3], $arguments);
      $arguments = array_filter(explode(',', $arguments[1]));
      if (count($arguments)) {
        echo "#### Arguments:\n";
        foreach ($arguments as $argument) {
          echo "- **" . trim($argument) . ":**\n";
        }
      }
    }
    exit;
  },
  'leaderboard' => function () {
    $users = resource('users')->all();

    usort($users, function ($a, $b) {
      return ($a['score'] - $b['score']) * -1;
    });

    $users = array_map(function ($user) {
      unset($user['pass']);

      return $user;
    }, $users);

    return array_slice($users, 0, 8);
  },
  'translates/' => function () {
    return resource('translations')->all();
  },
  'test/' => function () {
    return [time()];
  }
];
