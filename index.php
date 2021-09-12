<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, PATCH, POST, PUT, DELETE');

// Error logging
error_reporting(E_ALL);
ini_set('ignore_repeated_errors', TRUE);
ini_set('display_errors', true);
ini_set('error_log', '/errors.log');
ini_set('log_errors', TRUE);

spl_autoload_register(function ($nombre_clase) {
  include __DIR__ . "/resources/classes/" . lcfirst($nombre_clase) . ".php";
});

require_once('./utils.php');
require_once('./routes.php');
require_once('./resources.php');
require_once('./middlewares.php');
require_once('./run.php');

$startTime = time();

$res = run();
$sleep = [0, 0];
sleep(rand($sleep[0] * 1000, $sleep[1] * 1000) / 1000);

if (!is_string($res))
  echo json_encode($res);
else echo $res;
