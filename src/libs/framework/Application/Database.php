<?php

namespace Application;

use \ORM;

class Database
{    
	public static $isConfigured = false;    

    public static function configure()
    {
        $allConfig = config('database');		
        if( ! isset($allConfig['defaultdb'])) {
        	throw new \Exception("没有配置默认数据库。");
        }
        $connectionName = $allConfig['defaultdb'];
        if(!isset($allConfig['connections'][$connectionName])) {
            throw new \Exception(sprintf("找不到%s的数据库配置信息。", $connectionName));
        }
        $config = $allConfig['connections'][$connectionName];        		
        ORM::configure('driver_options', array(\PDO::ATTR_PERSISTENT => $config['pconnect']));        
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['hostname'], $config['database'], $config['charset']);
        $ormConfig = ['connection_string' => $dsn, 'username' => $config['username'], 'password' => $config['password'],];
        ORM::configure($ormConfig);        
        foreach( $allConfig['connections'] as $k=> $v) {
			if(array_key_exists('notidiorm', $v)) {
				continue;
			}
        	ORM::configure('driver_options', array(\PDO::ATTR_PERSISTENT => $v['pconnect']), $k);        	
        	$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $v['hostname'], $v['database'], $v['charset']);
        	$ormConfig = ['connection_string' => $dsn, 'username' => $v['username'], 'password' => $v['password'],];
        	ORM::configure($ormConfig, null, $k);
        }
		static::$isConfigured = true;
		
		
		
    }
	
	public static function setup($connectionName, $pconnect, $hostname, $database, $username, $password, $charset)
    {
		ORM::configure('driver_options', array(\PDO::ATTR_PERSISTENT => $pconnect), $connectionName);        	
		$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $hostname, $database, $charset);
		$ormConfig = ['connection_string' => $dsn, 'username' => $username, 'password' => $password,];
		ORM::configure($ormConfig, null, $connectionName);
    }
	
	public static function app($appid)
    {
		static $apps = [];
		
		if(!array_key_exists($appid, $apps)) {
			$apps[$appid] =  new class($appid){
			
				public $appid;
				
				public function __construct($appid)
				{
					$this->appid = $appid;
				}
				
				public function table($table, $primary = 'id')
				{
					return \DB::table($table, $primary, $this->appid);
				}
				
				public function getDb()
				{
					return \DB::table($this->appid);
				}
			};  
		}
		
		return $apps[$appid];
		
    }
    
    public static function table($table, $primary = 'id', $connectionName = 'defaultdb')
    {
    	if(!static::$isConfigured) {
    		static::configure();
    	}
    	ORM::reset_db();
        $tableOrm =  ORM::for_table($table, $connectionName);
        if($primary) {
        	$tableOrm->use_id_column($primary);
        }
        
        return $tableOrm;
    }
    
    public static function getDb($connectionName = 'defaultdb')
    {
    	if(!static::$isConfigured) {
    		static::configure();
    	}    
    	ORM::reset_db();
    	return ORM::get_db($connectionName);
    }

}