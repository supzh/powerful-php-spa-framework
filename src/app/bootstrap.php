<?php
define('APP_PATH', __DIR__.'/../../');
require __DIR__.'/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();

$dotenvFile = \APP_PATH . '/.env';

if( ! file_exists($dotenvFile)) {
	throw new \Exception('没有找到根目录.env配置文件，请根据.env.tpl模板设置.env配置文件。');
}

$dotenv->load($dotenvFile);


ini_set('date.timezone', config('app','date_timezone'));
date_default_timezone_set(config('app','date_timezone'));


