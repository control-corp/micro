<?php

use Micro\Application\Application;

$container = include __DIR__ . '/container.php';

$app = new Application($container);

$app->map('/', 'App\Index@index');

$app->map('/api[/{action}][/{id}]', function ($action) {
    return 'App\Api@' . lcfirst(Micro\Application\Utils::camelize($action));
})->setDefaults(['action' => 'index']);

return $app;