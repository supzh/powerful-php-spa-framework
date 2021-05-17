<?php

namespace Application;

class Session extends Storage
{
    public static function id()
    {
        return static::factory()->sessionId();
    }

    public static function destroy()
    {
        return static::factory()->sessionDestroy();
    }

    public function sessionId()
    {
        $this->isInit();
        return session_id();
    }
    
    public function sessionDestroy()
    {
        $this->isInit();
        session_destroy();
    }

    public function init()
    {    	
    	
    	if($sessionId = request()->input('sessid')) {        		
    		session_id($sessionId);    	
    	}
        parent::init();
        $this->on('before', function (self $storage){
            if(session_status() != 2) {
                session_start();
                $storage->dataStore = $_SESSION;
            }
        });
        $this->on('setDataStore', function ($storage, $key, $value){
            $_SESSION[$key] = $storage->dataStore[$key] = $value;
        });    	
    }
}