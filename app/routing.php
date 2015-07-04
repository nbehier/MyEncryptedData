<?php

/**
 * This file should be included from app.php, and is where you hook
 * up routes to controllers.
 *
 * @link http://silex.sensiolabs.org/doc/usage.html#routing
 * @link http://silex.sensiolabs.org/doc/providers/service_controller.html
 */

$app->get('/', 'app.default_controller:indexAction');
$app->get('/documents', 'app.default_controller:getDocumentsAction');
$app->post('/documents/{id}/encrypt', 'app.default_controller:encryptAction');
$app->post('/documents/new', 'app.default_controller:encryptAction');
$app->post('/documents/{id}/decrypt', 'app.default_controller:decryptAction');
$app->delete('/documents/{id}', 'app.default_controller:deleteAction');
