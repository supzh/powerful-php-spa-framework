<?php

namespace Application;

use \RedisClient as Redis;

class Log
{
    public static $logFile = 'app-log';
    public static $dontLog = false;
    public static $isCLI = false;
    public static $reqId;
    public static $isJob = false;
    public static $jobType;

	public static function setIsCLI()
	{
		static::$isCLI = true;
	}
	
    public static function info($msg = null, $logFile = null)
    {
        static::writeFile('info', $msg, $logFile);
    }

    public static function error($msg = null, $logFile = null)
    {
        if($logFile) {
            $logFile.='-error';
        } else {
            $logFile = static::$logFile.'-error';
        }
        static::writeFile('error', $msg, $logFile);
    }

    public static function prepare($msg)
    {
        if(is_string($msg)) {
            return $msg;
        } elseif (is_array($msg)){
        	return json_encode($msg);
            //return static::getArrayMsg($msg);
        } elseif ( $msg instanceof \Exception){    	
        	return $msg = str_replace("\n", "", (string)$msg);
        } else {
            return $msg;
        }
    }

    public static function getArrayMsg($msg)
    {
        $ret = "\r\n";
        if(is_array($msg)) {
            foreach ($msg as $k => $v) {
                $ret.= '  ' . $k . ' : ' . static::getArrayMsg($v) . "\r\n";
            }
            return $ret;
        } else {
            return $msg;
        }
    }

    public static function write($msg, $logFile)
    {
		return;
    	if(static::$dontLog) {
    		return;
    	}
    	static $handle;

        if(!$logFile) {
            $logFile =  static::$logFile;
        }
        $logMode = config('app', 'log_mode');
        if($logMode == 'redis') {
        	Redis::rpush('node:'.config('app','node').':applog', $msg);
        } elseif ($logMode == 'file') {
        	$handleFile = static::getHandleFile($logFile);
        	$handle[$handleFile] = @fopen($handleFile, 'a+');
        	@fwrite($handle[$handleFile], $msg);
        	@fclose($handle[$handleFile]);
        } elseif($logMode == 'database') {
			 if($msgjson = json_decode($msg)) {
				$c = Database::table('log')->create();
				$c->log_name = $msgjson->log_name;
				$c->req_id = $msgjson->req_id;
				$c->log_time = $msgjson->log_time;
				$c->log_type = $msgjson->log_type;
				$c->remote = $msgjson->remote;
				$c->req_uri = $msgjson->req_uri;
				$c->is_cli = static::$isCLI;
				$c->req_data = json_encode($msgjson->req_data);
				$c->app_name = $msgjson->app_name;
				$c->app_node = $msgjson->app_node;
				$c->app_env = $msgjson->app_env;
				//$c->server_ip = $msgjson->server_ip;
				$c->msg = json_encode($msgjson->msg);
				$c->save();
			}
        }
    }
    
    public static function getHandleFile($logFile)
    {
    	$node = config('app', 'node');
    	$node = str_replace(':','-',$node);
    	$path = 'log/'.$node.'_'.$logFile.'_'.date('Y').''.date('m').''.date('d');
    	$f = $path.'.txt';
    	$f = str_replace('\\', '', $f);
    	$filepath = appPath($f);
		return $filepath;
    }
    
    public static function getReqId()
    {
    	if (!static::$reqId) {
    		static::$reqId =  md5(date('YmdHis').uniqid().microtime());
    	}
    	return static::$reqId;
    }
    
    public static function refreshReqId()
    {
    	return static::$reqId =  md5(date('YmdHis').uniqid().microtime());
    }

    public static function writeFile($type, $msg, $logFile = null)
    {
        $msg = static::prepare($msg);
        static $appConfig;
        if(!$appConfig) {
        	$appConfig = config('app');
        }

        $remoteAddr = (isset($_SERVER['REMOTE_ADDR']))?$_SERVER['REMOTE_ADDR']:'';
        $requestUri = (isset($_SERVER['REQUEST_URI']))?$_SERVER['REQUEST_URI']:'';
        $textArr = [
        	'log_name' => $logFile,
            'req_id' => static::getReqId(),
            'log_time' =>date('Y-m-d H:i:s'),        	
            'log_type' =>$type,
            'remote' =>$remoteAddr,
            'req_uri' =>$requestUri,
        	'req_data' => $_REQUEST,
        	'app_name' => $appConfig['name'],
        	'app_node' => $appConfig['node'],
        	'app_env' => $appConfig['env'],
        	//'server_ip' => $appConfig['server_ip'],
            'msg' => ($jmsg = json_decode($msg))?$jmsg:$msg,
        ];
        
        $text = json_encode($textArr)."\r\n";
		
        static::write($text, $logFile);
    }
}