<?php

require __DIR__.'/../src/app/bootstrap.php';
$app = require_once __DIR__.'/../src/app/app.php';

$response = $app->handle(
    $request = Application\Request::make()
);

$app->run($request, $response);

