<?php

namespace Application;

class Application
{
	public $env;
    public $route;    
    public static $app;
    public static $appPath;
        
    public function setRoute(Route $route)
    {
        $this->route = $route;
    }

    public static function setAppPath($path)
    {
    	static::$appPath = $path;
    }

    public static function getAppPath($path='')
    {
    	return static::$appPath. $path;
    }
	
	public static function asyncPage($href, $httpGetParams)
	{
		if(!$href){return false;}
		$req = new Request();
		$req->isAsync = true;
		$req->type = 'get';
		$req->map = $href;
		$req->data = ['get'=>$httpGetParams,];
		$res = Application::$app->handle($req);
		ob_start();
		Application::$app->run($req, $res, true);
		$str = ob_get_clean();

		return $str;
	}
    
    public function getModule($module) {
    	
    	switch($module) {
    		case 'request':
				
				if(!$this->request) {
					$this->request =  Request::make();
				}
    			return $this->request;
    			break;
    		
    		case 'response':
				if(!$this->response) {
					$this->response =  Response::create($this->request);
				}
    			return $this->response;
    			break;
    	}
    }

    public function __construct()
    {
    	$this->envInit();
    	Timeline::getInstance()->start('App');
    	switch(config('app', 'session', 'file')) {
    		case 'database':
    			if ('cli' != php_sapi_name())
    				session_set_save_handler(new \SysSessionHandler(), true);    			
    			break;
    	}    	
        set_error_handler([Application::class, 'error']);
        if(!static::$app) {
            static::$app = $this;
        }
    }
    
    public function envInit()
    {
    	$this->env = $env = config('app','env');
    	if($env == 'dev') {
    		ini_set('display_errors', '1');
    		error_reporting(E_ALL ^ E_NOTICE);
    	}
    	if($env == 'prod') {
    		ini_set('display_errors', '0');
    		error_reporting(E_ALL ^ E_NOTICE);
    	}
    	ini_set('date.timezone', config('app','date_timezone'));
    	date_default_timezone_set(config('app','date_timezone'));
    }
    
    public static function getInstance()
    {
    	if(!static::$app) {
    		static::$app = new static();
    	}
    	
    	return static::$app;
    }

    public static function error($errno, $errstr, $errfile, $errline)
    {
        Log::error(['errno'=>$errno, 'errstr'=>$errstr, 'errfile'=>$errfile, 'errline'=>$errline], 'app-handle');
    }

    public function handle(Request $request = null)
    {
        if($request === null) {
            $request = Request::make();
        }
        if( ! $this->route) {
            $this->setRoute(new Route());
        }
		
        return Response::create($request);
    }

    public function run(Request $request, Response $response, $isAsync = false)
    {
        $this->response = $response;
        if(!$isAsync){ob_start();}
        try {
	
            echo $this->terminate($request);
			
        } catch(AppAPIException $e) {
        	if(!$code = $e->getCode()) {
        		$code = 400;
        	}
			$this->response->setHeader('Access-Control-Allow-Origin', '*');
        	$this->response->setHeader('Content-Type', 'application/json; charset=utf-8');
            $this->response->setStatus($code, $e->getMessage());
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            Log::error($e, 'restfulapi-exception');
        } catch (\Exception $e) {        	
            echo $e->getMessage();
            Log::error($e, 'app-exception');
        }
        if(!$isAsync){
			$responseContent = ob_get_clean();
			$this->finish($responseContent);
		}
    }

    public function finish($responseContent = null)
    {
    	Timeline::getInstance()->end('App');
    	$this->response->setHeader('X-Runtime', Timeline::getInstance()->getUseTime('App'));
    	if($reqId = Log::getReqId()) {
    		$this->response->setHeader('X-Request-Id', $reqId);
    	}    
    	
        $this->response->end($responseContent);
        
        Log::info(['timeline' => Timeline::getInstance()->getTimeline(), 'response'=>($jrc=json_decode($responseContent))?$jrc:$responseContent , 'request'=>json_decode((string)$this->route->request)], 'app-request');
        exit;        
    }

    public function terminate(Request $request)
    {
    	$this->request = $request;
        if($this->route->match($request)) {
        	
            $next = function($request){
                return $this->execute($request);
            };
            $request->routeMatchInfo = $this->route->matchInfo;
         
            if(is_callable($middleware = $this->route->matchInfo['middleware'])) {
                return $middleware($request, $next);
            } elseif(is_string($middleware)) {
            	
            	if(strpos($middleware, '@') === false) {
            		$middlewareController = new $middleware();
            		return $middlewareController->handle($request, $next);
            	} else {
            		list($middleClass, $middleMethod) = explode('@', $middleware);              		            		
            		$middlewareController = new $middleClass();
            		return $middlewareController->$middleMethod($request, $next);
            	}            	               
            } else {
                return $next($request);
            }
        }
        if(is_callable($notFound = Route::$notFound)) {
            return $notFound();
        }
    }

    public function execute(Request $request)
    {    	
        $method = $this->route->matchInfo['executeMethod'];
        $methodName = $this->route->matchInfo['executeMethodName'];
        $params = $request->routeMatchInfo['mapParams'];
        $checkFirstParamsIsRequest = function($refFunc) use ($params, $request) {
            if($refParams = $refFunc->getParameters()) {
                if ($firstRefParam = $refParams[0]) {
                	if($firstRefParam->getClass()) {
	                    if ($firstRefParam->getClass()->name == 'Application\Request') {
	                        array_unshift($params, $request);
	                    }
                	}
                }
            }

            return $params;
        };
 
        if (is_string($method)) {
            //实例化控制器
            $controller = new $method();
            $controller->app = $this;
            if($methodName) {
                if (!method_exists($controller, $methodName)) {
                    throw new \Exception(sprintf('在 %s 找不到方法 %s。', $method, $methodName));
                }

                return call_user_func_array(
                    [$controller, $methodName],
                    $checkFirstParamsIsRequest(new \ReflectionMethod($controller, $methodName))
                );
            } else {
                return $controller;
            }
        }
        
        return  call_user_func_array($method, $checkFirstParamsIsRequest(new \ReflectionFunction($method)));        
    }

}
