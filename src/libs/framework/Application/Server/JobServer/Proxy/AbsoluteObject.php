<?php

namespace Application\Server\JobServer\Proxy;

class AbsoluteObject
{
	public $instance;

	public function __construct($className, $classArgs)
	{
		if(!method_exists($className, '__construct')) {
			$this->instance =new $className();
		} else {					
			$refMethod = new \ReflectionMethod($className,  '__construct');
			$params = $refMethod->getParameters();
			$realArgs = array();
			$argIndex = 0;
			foreach ($params as $key => $param) {
				if ($param->isPassedByReference()) {
					$realArgs[$key] = &$classArgs[$argIndex];
				} else {
					if (isset($classArgs[$argIndex])) {
						$realArgs[$key] = $classArgs[$argIndex];
					} else {
						$realArgs[$key] = null;
					}
				}
				++$argIndex;
			}
			$refClass = new \ReflectionClass($className);
	
			$this->instance = $refClass->newInstanceArgs((array) $realArgs);
		}
	}

	public function getInstance()
	{
		return $this->instance;
	}
}
