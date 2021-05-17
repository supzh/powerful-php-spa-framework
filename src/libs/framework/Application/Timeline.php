<?php

namespace Application;

class Timeline
{
	public $m;	
	public $nodeName;
	private static $i;	
	
	public static function getInstance()
	{
		if (null === self::$i) {
			self::$i = new self();
		}

		return self::$i;
	}

	public function start($name)
	{
		if($this->nodeName) {
			$name = $this->nodeName.'.'.$name;
		}
		$this->m[$name]['start'] = microtime($get_as_float = true);
		$this->nodeName = $name;
	}

	public function end($originName)
	{
		$name = substr($this->nodeName, 0, stripos($this->nodeName, $originName)).$originName;
		$this->m[$name]['end'] = microtime($get_as_float = true);
		$this->m[$name]['use'] = $this->m[$name]['end'] - $this->m[$name]['start'];
		
		$this->nodeName = str_replace('.'.$originName, '', $this->nodeName);
	}
	
	public function getUseTime($name)
	{
		return isset($this->m[$name]['use'])?$this->m[$name]['use']:null;
	}
	
	public function getTimeline()
	{
		return $this->m;
	}

	public function __toString()
	{
		return json_encode($this->m);
	}
}
