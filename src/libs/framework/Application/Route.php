<?php

namespace Application;

class Route
{
    public $request;
    public $matched = false;
    public $matchInfo = array();
    public static $notFound;
    public static $routesMiddleware = array();
    public static $routes = array();
    protected static $routesDescription = array();

    public static function add($type, $paramKey, $callable, $description='')
    {
        if(is_string($type)) {
            static::$routes[strtolower($type)][$paramKey] = $callable;
            static::$routesDescription[strtolower($type)][$paramKey] = $description;
        } elseif(is_array($type)) {
            foreach( $type as $typeVal) {
                static::$routes[strtolower($typeVal)][$paramKey] = $callable;
                static::$routesDescription[strtolower($typeVal)][$paramKey] = $description;
            }
        }

        return new RouteMiddleware($type, $paramKey, $callable);
    }

    public static function get($paramKey, $callable)
    {
        return static::add('GET', $paramKey, $callable);
    }

    public static function post($paramKey, $callable)
    {
        return static::add('POST', $paramKey, $callable);
    }
    
    public static function delete($paramKey, $callable)
    {
    	return static::add('DELETE', $paramKey, $callable);
    }

    public static function request($paramKey, $callable)
    {
        return static::add(['GET', 'POST'], $paramKey, $callable);
    }
    
    public static function any($paramKey, $callable)
    {
    	return static::add(['GET', 'POST', 'DELETE', 'PUT', 'PATCH'], $paramKey, $callable);
    }

    public static function console($paramKey, $callable, $description = '')
    {
        return static::add('CONSOLE', $paramKey, $callable, $description);
    }

    public static function notFound($callable)
    {
        static::$notFound = $callable;
    }

    public function getRouteMap($type)
    {
        if(isset(static::$routes[$type])) {
            return static::$routes[$type];
        }
    }

    public function __construct()
    {
        if( ! static::$notFound) {
            $route = $this;
            static::notFound(function() use ($route) {
                if($route->request->type == 'console') {
                    echo "\n";
                    if(!isset(static::$routes['console'])) {
                        echo "目前没有可用命令。\n\n";
                    } else {
                        $showCmdList = function() {
                            echo "目前可用命令:\n\n";
                            $cmdList = static::$routes['console'];
                            foreach ($cmdList as $cmd => $call) {
                                echo "  " . $cmd;
                                if ($desc = static::$routesDescription['console'][$cmd]) {
                                    echo " : ".$desc . "\n";
                                } else {
                                    echo "\n";
                                }
                            }
                        };
                        if($route->request->map == '') {
                            $showCmdList();
                        } else {
                            echo "您输入的命令".$route->request->map." 无效。\n\n";
                            $showCmdList();
                        }
                    }
                    echo "\n";
                } else {
                    echo "找不到您所访问的页面。\n";
                }
            });
        }
    }

    public function match(Request $request)
    {
        $this->request = $request;
        $mapParams = $matchInfo = [];
        $requestType = $request->getType();
        $requestMap = $request->getMap();
        if( ! $maps = $this->getRouteMap($requestType)) {
            return $this->matched;
        }        
        
        if($requestType == 'console') {
            $currentRequestMapKeys = explode(' ', $requestMap);
            $currentRequestMapKeys = [$currentRequestMapKeys[0]];
        } else {        	
        	$requestMap = str_replace('//', '/', $requestMap);
			$hasPrefix = strtoUpper(substr($requestMap, -5, 5));
			if($hasPrefix == '.HTML') {
				$requestMap = substr($requestMap, 0, strlen($requestMap)-5);
			}
            $currentRequestMapKeys = explode('/', $requestMap);
        }
		
        $mapsKeys = array_keys($maps);

        foreach($mapsKeys as $mapKey) {
            $paramKey = explode('/',$mapKey);
			//route / arr
            if(count($currentRequestMapKeys) != count($paramKey)) {
                //参数段落个数不匹配
				$this->matched = false;

				if( @strpos($mapKey, '(*)' === false)) {
					continue;
				}
				
				$checkMatched = [];
				foreach($currentRequestMapKeys as $i=>$ck) {
					
					if(!array_key_exists($i, $paramKey)) {
						continue 2;
					}
					
					$a = substr($paramKey[$i], 0, 1);
                    $e = substr($paramKey[$i], -1, 1);
                    if($a == '(' and $e == ')') {
                        //自变段落名
						if($paramKey[$i] == '(*)') {
						  $checkMatched[] = true;
						  break;
						} else {
							$checkMatched[] = false;
                            continue;
						}
                    } else {
                        if(strtoupper($paramKey[$i]) == strtoupper($currentRequestMapKeys[$i])) {                        	
                            //参数段落名匹配
                            $checkMatched[] = true;
                        } else {
                            $checkMatched[] = false;
                            continue;
                        }
                    }
				}
				$isMatched = true;
                foreach($checkMatched as $v) {
                    if(!$v) {
                        $isMatched = false;
                        break;
                    }
                }
                $this->matched = $isMatched;
            } else {
                $checkMatched = [];
                for($i=0;$i<count($currentRequestMapKeys);$i++) {
                    $a = substr($paramKey[$i], 0, 1);
                    $e = substr($paramKey[$i], -1, 1);
                    if($a == '(' and $e == ')') {
                        //自变段落名
                        $mapParams[substr($paramKey[$i], 1, strlen($paramKey[$i])-2)] = $currentRequestMapKeys[$i];
                        $checkMatched[] = true;
                    } else {
                        if(strtoupper($paramKey[$i]) == strtoupper($currentRequestMapKeys[$i])) {                        	
                            //参数段落名匹配
                            $checkMatched[] = true;
                        } else {
                            $checkMatched[] = false;
                            continue 2;
                        }
                    }
                }
                $isMatched = true;
                foreach($checkMatched as $v) {
                    if(!$v) {
                        $isMatched = false;
                        break;
                    }
                }
                $this->matched = $isMatched;
            }

            if($this->matched) {
                if(is_string($maps[$mapKey])) {
                    if(strpos($maps[$mapKey], '@') === false) {
                        $executeMethod = $maps[$mapKey];
                        $executeMethodName = null;
                    } else {
                        $class = explode('@', $maps[$mapKey]);
                        $executeMethod = $class[0];
                        $executeMethodName = $class[1];
                    }
                } else {
                    $executeMethod = $maps[$mapKey];
                    $executeMethodName = null;
                }
                $matchInfo = [
                    'requestMap' => $requestMap,
                    'mapKey' => $mapKey,
                    'mapParams' => $mapParams,
                    'executeMethod'=> $executeMethod,
                    'executeMethodName' => $executeMethodName,
                    'requestType' => $request->getType(),
                    'middleware' => static::getRouteMiddleware($request->getType(), $mapKey)
                ];
                $request->routeMatchInfo = $this->matchInfo = $matchInfo;
                break;
            }
        }

        return $this->matched;
    }

    public static function getRouteMiddleware($type, $key)
    {
        if(isset(Route::$routesMiddleware[$type][$key])){
            return Route::$routesMiddleware[$type][$key];
        }
    }
}
