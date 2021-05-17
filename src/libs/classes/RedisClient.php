<?php

/**
 * Created by PhpStorm.
 * User: nathan
 * Date: 2017/1/12
 * Time: 下午4:40
 */
class RedisClient
{
	public static $instance;
	public $client;
	
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new static();
		}

		return self::$instance;
	}
	
	public static function getConnectConfig()
	{
		static $connectstring;
		
		if($connectstring === null) {
			$connectstring = config('redis');
		}
		
		return $connectstring;
	}
	
	public function __construct()
	{
		$option =  config('redis','options');
		
		if(!$option['parameters']['password']) {
			$option = null;
		}
		
		$this->client = new Predis\Client(static::getConnectConfig(), $option); 
	}
	
	public function __call($func, $args)
	{
		return call_user_func_array([$this->client, $func], $args);
	}
	
	public static function __callStatic($func, $args)
	{
		return call_user_func_array([new static(), $func], $args);
	}
	
	public function __destruct()
	{
		$this->client->disconnect();
	}
}