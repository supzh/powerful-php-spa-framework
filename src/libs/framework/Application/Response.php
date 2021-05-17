<?php

namespace Application;

class Response
{
    public $request;
    public $headers;

    public function __construct(Request $request = null)
    {
    	if( ! $request) {
    		$request = new Request();
    	}
    	
        $this->request = $request;
    }

    public static function create($request = null)
    {              
        return new self($request);
    }
   

    public function setStatus($code, $msg = null)
    {
        $this->headers[] = function() use ($code, $msg) {
            header(sprintf('HTTP/1.1 %s %s', $code, $msg));
        };
    }
    
    public function redirect($to)
    {   
    	$this->setStatus(302);
    	$this->setHeader('location', $to);
    	app()->finish();
    }
    
    public function setHeader($name, $value)
    {
    	$headerValue = $name . ': ' . $value;
    	$this->headers[] = function() use ($headerValue) {
    		header($headerValue);
    	};
    }

    public function end($content)
    {    	
        $headers = $this->headers;        
        if($headers) {
            foreach($headers as $setHeaderCallback) {
                $setHeaderCallback();
            }
        }
        echo $content;
    }
    
    public function finish($content)
    {
    	app()->finish($content);
    }
    
    public function json($content, $httpcode = null)
    {
		$this->setHeader('Access-Control-Allow-Origin', '*');
    	$this->setHeader('Content-Type', 'application/json; charset=utf-8');
    	if($httpcode) {
    		$this->setStatus($httpcode, '');
    	}
    	app()->finish(json_encode($content, JSON_UNESCAPED_UNICODE));
    }
    
    public function download($path_name, $save_name)
    {
    	ob_end_clean();
    	$hfile = fopen($path_name, "rb") or die("Can not find file: $path_name\n");
		header('Access-Control-Allow-Origin: *');
    	header("Content-type: application/octet-stream");
    	header("Content-Transfer-Encoding: binary");
    	header("Accept-Ranges: bytes");
    	header("Content-Length: ".filesize($path_name));
    	header("Content-Disposition: attachment; filename=\"$save_name\"");
    	while (!feof($hfile)) {
    		echo fread($hfile, 32768);
    	}
    	fclose($hfile);
    	exit;
    }
}
