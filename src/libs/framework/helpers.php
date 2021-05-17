<?php 

use Application\Application;
use Application\Session;
use Application\Config;
use Application\Log;
use SuperClosure\Serializer;

function getLocalIP() {
	$preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";

	//获取操作系统为linux类型的本机IP真实地址
	exec("ifconfig", $out, $stats);
	if (!empty($out)) {
		if (isset($out[1]) && strstr($out[1], 'addr:')) {
			$tmpArray = explode(":", $out[1]);
			$tmpIp = explode(" ", $tmpArray[1]);
			if (preg_match($preg, trim($tmpIp[0]))) {
				return trim($tmpIp[0]);
			}
		}
	}
	$server_hostname=gethostname();
    $server_hostname .= ".";
    return gethostbyname($server_hostname);
}

function getORM($dbname = 'SMS', $isDev = false)
{
	static $orms = array();
	
	if( ! array_key_exists($dbname, $orms) ) {
		$orms[$dbname] = new \DoctrineORM($dbname, $isDev);
	}
	return $orms[$dbname];
}

function getEM($dbname = 'SMS')
{
	return \getORM($dbname)->getEM();
}

function getasyncjobqueuenum()
{
	$node = config('app', 'node');
	if($job = \RedisClient::getInstance()->keys('node:'.$node.':job:*')) {
		
		$r = [];
		foreach($job as $jobk) {
			$r[$jobk] = \RedisClient::getInstance()->llen($jobk);
		}		
		return $r;
	}
}

function updateJobProgress($appid, $jobname, $progress, $err = null)
{
	try {
		if(!$jl = \DB::table('joblist')->where('jobname', $jobname)->where('app_id', $appid)->findOne() ) {
			return false;
		}
		if($progress) {
			$jl->progress = $progress;
		}
		if($err) {
			$jl->err = $err;
		}
		
		$jl->save();
	} catch(\Exception $e) {
		\Application\Log::error('updateJobProgress('.json_encode(func_get_args()).') Err: ' . $e->getMessage());
	}
}

function sendMsgtoAppIdUser($appid, $msg)
{
	\RedisClient::getInstance()->rpush('appmsg',  $msg);
}

function addLockJob($appid, $jobname, $callback)
{
	if($jl = \DB::table('joblist')->where('jobname', $jobname)->where('app_id', $appid)->findOne() ) {
		throw new \Exception('任务已存在。');
	}
	
	$jl = \DB::table('joblist')->create();
	$jl->app_id = $appid;
	$jl->jobname = $jobname;
	if(!$jl->save()) {
		throw new \Exception('创建Job失败。');
	}
	$joblist_id = $jl->id();
	$asyncCallback = function() use($appid,$jobname,  $callback, $joblist_id) {
		\DB::configure();
		try {
			$res = $callback();
			\Application\Log::info('joblist:'.$joblist_id.' finished:' . json_encode($res));
			
			\sendMsgtoAppIdUser($appid, $jobname . '已完成('.date('Y-m-d H:i:s').') ');
			
			if($jl = \DB::table('joblist')->where('id', $joblist_id)->findOne() ) {
				$jl->delete();
			}
			
		} catch(\Exception $e) {
			\Application\Log::error((string)$e);
			\Mailer::sendExcpetion($e, '('.$appid.')lockjob run err ' . $jobname);
			if($jl = \DB::table('joblist')->where('id', $joblist_id)->findOne() ) {
				//$jl->err = $e->getMessage();
				$jl->delete();
				\sendMsgtoAppIdUser($appid, $jobname . '出现异常('.$e->getMessage().')('.date('Y-m-d H:i:s').') ');
			}
		}
		
	};
	if(\async($asyncCallback, $jobname)) {
		return $joblist_id;
	} else {
		return false;
	}
}

function async($callback, $jobname = 'noname',int $job_id = null)
{
	$serializer = new Serializer();
	
	if($serialized = $serializer->serialize($callback)) {
		
		try {
			$serializer->unserialize($serialized);
		} catch (\Exception $e) {
			\Application\Log::error((string)$e);
			\Mailer::sendExcpetion($e, 'add async err');
			return false;
		}
		
		if(!$job_id) {
			
			if(config('job', 'async_type') == 'db') {
				if($jobp = \DB::table('jobprocess')->where('status', 'waitjob')->where('type', 1)->findOne()) {
					$job_id = $jobp->processid;
				} else {
					$job_id	= mt_rand(1, config('job', 'max_process'));
				}
			} else {
				
				$job_id	= mt_rand(1, config('job', 'max_process'));
			}
		} 
		
		$node = config('app', 'node');
		try {
			\RedisClient::getInstance()->rpush('node:'.$node.':job:'.$job_id, json_encode([ $serialized, $jobname  ]));
		} catch (\Exception $e) {
			\Application\Log::error((string)$e);
			\Mailer::sendExcpetion($e, 'add async redis rpush err');
			return false;
		}
		return true;
	}
	unset($serializer);
	return false;
}

function process($callback, $jobname)
{
	
	$serializer = new Serializer();
	
	if($serialized = $serializer->serialize($callback)) {
		
		try {
			$serializer->unserialize($serialized);
		} catch (\Exception $e) {
			\Application\Log::error((string)$e);
			\Mailer::sendExcpetion($e, 'add process err');
			throw $e;
		}
		
		if($jobp = \DB::table('jobprocess')->where('jobname', $jobname)->where('type', 2)->findOne()) {
			return false;
		}
		
		$node = config('app', 'node');
		
		return \RedisClient::getInstance()->rpush('node:'.$node.':process', json_encode([ $serialized, $jobname  ]));
	}
	unset($serializer);
	return false;
}


function stopProcess($jobname)
{
	
	$node = config('app', 'node');
	
	\RedisClient::getInstance()->rpush('node:'.$node.':process_stop', $jobname);
}


if( ! function_exists('log')) {

	function log($str, $file = '', $iserr = false)
	{
		if(!$file) {
			$file = 'app';
		}
		if($iserr) {
			Log::error($str, $file);
		} else {
			Log::info($str, $file);
		}
	}
}


if( ! function_exists('app')) {

	function app()
	{
		return Application::getInstance();
	}
}

if( ! function_exists('appPath')) {

	function appPath($path = '')
	{
		return \APP_PATH.$path;
	}
}

if( ! function_exists('session')) {
	
	function session($key = null, $value = null)
	{
		if($value) {
			return Session::set($key, $value);
			
		} else {
			if($key) {
				return Session::get($key);
			}
		}
	
		return new class(){
			public function get($key, $arrkey = null)
			{				
				$res =  Session::get($key);
				if($arrkey && is_array($res) && isset($res[$arrkey])) {
					return $res[$arrkey];
				}
				return $res;
			}
			
			public function set($key, $value)
			{
				return Session::set($key, $value);
			}
			
			public function id()
			{
				return Session::id();
			}
			
			public function destroy()
			{
				return Session::destroy();
			}
		};
	}
}

if( ! function_exists('config')) {

	function config($file, $key = '', $default = '', $reload = false)
	{		
		return Config::get($file, $key, $default, $reload);
	}
}

if( ! function_exists('response')) {

	function response()
	{
		return app()->getModule('response');
	}
}

if( ! function_exists('request')) {

	function request()
	{		
		return app()->getModule('request');
	}
}

if( ! function_exists('redirect')) {
	
	function redirect($to)
	{
		return app()->getModule('response')->redirect($to);
	}
}
if( ! function_exists('humanReadSize')) {
		
	function humanReadSize($bytes, $s = 0)
	{
		$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
		$base = 1024;
		$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		
		return sprintf('%1.'.$s.'f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
	}

}

/**
 * 生成随机码
 * randomStr().
 *
 * @param int    $len      长度
 * @param string $prefix   前缀
 * @param bool   $isString 是否是数字 true/false
 *
 * @author zhangle
 * @date 2015-04-23
 */

if( ! function_exists('randomStr')) {

	function randomStr($len = 6, $prefix = '', $isString = false)
	{
		if ($isString == true) {
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1256789';
		} elseif ($isString == false) {
			$chars = '1256789';
		}
		for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; ++$i) {
			$str .= $chars[rand(0, $lc)];
		}
		
		return $prefix.$str;
	}	
}

if( ! function_exists('mkPath')) {

	/**
	 * @mkPath()创建多级目录
	 *
	 * @param  $mkPath  string  路径
	 * @param  $mode    string  权限
	 *
	 * @author	zhangle
	 * @date	2015-04-24
	 */
	function mkPath($mkPath, $mode = 0777)
	{
		$pathArray = explode('/', $mkPath);
		foreach ($pathArray as $value) {
			if (!empty($value)) {
				if (empty($path)) {
					$path = $value;
				} else {
					$path .= '/'.$value;
				}
				$path= '/'.$path;
				if (is_dir($path)) {
					continue;
				}
				@mkdir($path, $mode, true);
			}
		}
	}	
}


function readAllFiles($root = '.')
{
	$files  = array('files'=>array(), 'dirs'=>array());
	$directories  = array();
	$last_letter  = $root[strlen($root)-1];
	$root  = ($last_letter == '\\' || $last_letter == '/') ? $root : $root.DIRECTORY_SEPARATOR;
	
	$directories[]  = $root;
	
	while (sizeof($directories)) {
		$dir  = array_pop($directories);
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file == '.' || $file == '..') {
					continue;
				}
				$file  = $dir.$file;
				if (is_dir($file)) {
					$directory_path = $file.DIRECTORY_SEPARATOR;
					array_push($directories, $directory_path);
					$files['dirs'][]  = $directory_path;
				} elseif (is_file($file)) {
					$files['files'][]  = $file;
				}
			}
			closedir($handle);
		}
	}
	
	return $files;
}