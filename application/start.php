<?php

use Micro\Application\Application;

$container = include __DIR__ . '/container.php';

$app = new Application($container);
$app->registerDbBinder();

$app->map('/', 'App\Index@index', 'home');

$app->map('/api[/{action}][/{id}]', function ($action, $id) {

    return 'App\Api@' . $action;

})->setDefaults(['action' => 'index']);

return $app;