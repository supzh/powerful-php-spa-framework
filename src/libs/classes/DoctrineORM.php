<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

class DoctrineORM
{
	public $dbname;
	public $entityManager;
	public $isDevMode;
	public $consoleHelperSet;
	
	public function __construct($dbname, $isDevMode = false)
	{
		$this->dbname = $dbname;
		$this->isDevMode = $isDevMode;
	}
	
	public function getEM()
	{
		if( ! $this->entityManager) {
			$paths = [ \APP_PATH .  "database/".ucfirst($this->dbname) ];
			$dbParams = static::getDoctrineConfig($this->dbname);
			$config = Setup::createYAMLMetadataConfiguration($paths, $this->isDevMode);
			
			$this->entityManager = EntityManager::create($dbParams, $config);
		}
		
		return $this->entityManager;
	}
	
	public function getConsoleHelper($dbname = 'SMS')
	{
		if( ! $this->entityManager) {
			$this->getEM($dbname);
		}
		
		if( ! $this->consoleHelperSet) {
			$this->consoleHelperSet = ConsoleRunner::createHelperSet($this->entityManager);
		}
		
		return $this->consoleHelperSet;
	}
	
	
	public static function getDoctrineConfig($name = null)
	{
		if($name == null) {
			$name = \config('database', 'SMS');
		}
		
		$configs = \config('database', 'connections');
		
		if(!array_key_exists($name, $configs)) {
			throw new \Exception('找不到Database配置文件' . $name);
		}
		$config = $configs[$name];

		if(array_key_exists('url', $config)) {
			return ['url' => $config['url']];
		} else {
			
			if(!array_key_exists('driver', $config)) {
				$config['driver'] = 'pdo_mysql';
			}
			if(!array_key_exists('port', $config)) {
				$config['port'] = '3306';
			}
			
			$dbParams = array(
				'driver'   => $config['driver'],
				'user'     => $config['username'],
				'password' => $config['password'],
				'host' =>     $config['hostname'],
				'port' =>   $config['port'],
				'charset' => $config['charset'],
				'dbname'   =>  $config['database'],
			);
			
			return $dbParams;
		}
	}

}



