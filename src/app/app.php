<?php

use Application\Application;

Application::setAppPath(\APP_PATH);

$config = config('app');

$app = new Application($config);

require __DIR__.'/routes.php';

return $app;

