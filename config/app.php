<?php

return [
	'name' => getenv('APP_NAME'),
	'version' => getenv('APP_VERSION'),
	'node' => getenv('APP_NODE'),
	'env'=> getenv('APP_ENV'), //prod // testing
	'debug' =>  getenv('APP_DEBUG'),
	'server_port'=> getenv('APP_SERVER_PORT'),
	'server_host'=> getenv('APP_SERVER_HOST'),
	"date_timezone" => getenv('DATE_TIMEZONE'),
	'log_mode' => getenv('LOG_MODE'), //file // redis / database
	'session' =>  getenv('SESSION'), //file / database
];
