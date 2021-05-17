<?php

return [
    'defaultdb' => 'defaultdb',
    'connections' => [
        'defaultdb' => [
			'url' => getenv('DB_DEFAULT_URL'),
            'hostname' => getenv('DB_DEFAULT_HOST'),
            'database' =>  getenv('DB_DEFAULT_DB'),
            'username' =>  getenv('DB_DEFAULT_USER'),
            'password' =>  getenv('DB_DEFAULT_PWD'),
            'charset' => getenv('DB_DEFAULT_CHAR'),
            'pconnect' => getenv('DB_DEFAULT_PCON'),
            'log'=> getenv('DB_DEFAULT_LOG'),
        ],
		'SMS' => [
            'notidiorm' => true,
            'url' => getenv('DB_DEFAULT_URL'),
        ],
    ],
	'timeout_reload' => 5
];



