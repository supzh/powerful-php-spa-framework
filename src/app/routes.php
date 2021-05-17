<?php

use Application\Application;
use Application\Route;
use Application\Request;

Route::request('/to', function(){
	$tplstr = Application::asyncPage($_GET['href'], $_GET);
	
	echo json_encode(['code' => 0, 'content' => $tplstr]);
});

Route::request('/', function(Request $request){
	include __DIR__ . '/../../tpl/index.html';
});

Route::request('/index', function(Request $request){
	include __DIR__ . '/../../tpl/index.html';
});

Route::request('/sec', function(Request $request){
	include __DIR__ . '/../../tpl/sec.html';
});
