<?php 

namespace Application;

class Storage
{
    public static $storage = array();
    public $isInit = false;
    public $data = array();
    public $dataStore = array();
    public $dataStoreCallbacks = array(
        'setDataStore' => null,
        'getDataStore' => null,
        'before' => null,
    );

    public static function all()
    {
        return static::factory()->getDataStore();
    }

    public function getDataStore()
    {
        $this->isInit();
        return $this->dataStore;
    }

    public static function factory()
    {
        if(!isset(static::$storage[static::class])) {
            static::$storage[static::class] = new static();
        }
        return static::$storage[static::class];
    }

    public function __construct()
    {
        $this->init();
    }

    public static function get($name = null)
    {
    	if($name === null) {
    		return static::all();
    	}
    	
        return static::factory()->getValue($name);
    }

    public static function set($name, $value)
    {
        return static::factory()->setKeyValue($name, $value);
    }

    public function init()
    {
        $this->on('getDataStore', function ($storage, $key){
            if(!isset($storage->data[$key])) {
                if(isset($storage->dataStore[$key])) {
                    $storage->data[$key] = $storage->dataStore[$key];
                } else {
                    $storage->data[$key] = null;
                } 
            }            
            return $storage->data[$key];
        });
        
        $this->on('setDataStore', function ($storage, $key, $value){
            $storage->dataStore[$key] = $value;
        });
    }

    public function on($name, callable $store)
    {
        $this->dataStoreCallbacks[$name] = $store;
    }
    
    public function isInit()
    {
        if(!$this->isInit)  {
            $this->init();
            $this->isInit = true;
        }
        if(is_callable($this->dataStoreCallbacks['before'])) {
            $this->dataStoreCallbacks['before']($this);
        }
    }
    
    public function getValue($key)
    {
        $this->isInit();
        if(!isset($this->data[$key])) {
            if(is_callable($this->dataStoreCallbacks['getDataStore'])) {
                return $this->dataStoreCallbacks['getDataStore']($this, $key);
            }
        } else {
            return $this->data[$key];
        }                                                      
    }
  
    public function setKeyValue($key, $value)
    {
        $this->isInit();
        $this->data[$key] = $value;
        if(is_callable($this->dataStoreCallbacks['setDataStore'])) {
            $this->dataStoreCallbacks['setDataStore']($this, $key, $value);
        }
    }
}