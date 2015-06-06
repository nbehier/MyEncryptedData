<?php
$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

$startTime = microtime(true);
require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../app/app.php';
if ($app instanceof Silex\Application) {
    $app->run();
} else {
    echo 'Failed to initialize application.';
}