<?php

/**
 * This file should be included from app.php, and is where you hook
 * up routes to controllers.
 *
 * @link http://silex.sensiolabs.org/doc/usage.html#routing
 * @link http://silex.sensiolabs.org/doc/providers/service_controller.html
 */

$app->get('/', 'app.default_controller:indexAction');
$app->get('/encrypt', 'app.default_controller:encryptAction');
$app->get('/decrypt', 'app.default_controller:decryptAction');
