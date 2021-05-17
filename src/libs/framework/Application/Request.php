<?php

namespace Application;

class Request
{
	public $isAsync = false;
    public $type;
    public $map;
    public $data;
    public $num;
    public $fields;
    public $routeMatchInfo;

    public static function make($requestType = null, $swooleServer = null)
    {
        if (null !== $requestType && null !== $swooleServer) {
            switch ($requestType) {
                case 'swoole':
                    return new self($requestType, $swooleServer);
                    break;
                default:
                    throw new \Exception(sprintf('不支持目标“%s”请求。', $requestType));
                    break;
            }
        }
      
       return new self($requestType, $swooleServer);
    }

    public function __construct($requestType = null, $swooleServer = null)
    {
        if (null !== $requestType && null !== $swooleServer) {
            switch (strtolower($requestType)) {
                case 'swoole':
                    $this->setSwooleParams($swooleServer);
                    break;
                default:
                    throw new \Exception(sprintf('不支持目标“%s”请求。', $requestType));
                    break;
            }
        } else {
            $this->setParams();
        }
    }
	
	
	public function map($key)
	{
		return $this->routeMatchInfo['mapParams'][$key];
	}
	
    public function getMap()
    {
        return $this->map;
    }

    public function getType()
    {
        return $this->type;
    }

    public function get($key = null)
    {
        if (null == $key) {
            if (isset($this->data['get'])) {
                return $this->data['get'];
            } else {
                return;
            }
        } else {
            if (isset($this->data['get'][$key])) {
                return $this->data['get'][$key];
            } else {
                return;
            }
        }
    }

    public function post($key = null)
    {
        if (null == $key) {
            if (isset($this->data['post'])) {
                return $this->data['post'];
            } else {
                return;
            }
        } else {
            if (isset($this->data['post'][$key])) {
                return $this->data['post'][$key];
            } else {
                return;
            }
        }
    }

    public function cookie($key = null)
    {
        if (null == $key) {
            if (isset($this->data['cookie'])) {
                return $this->data['cookie'];
            } else {
                return;
            }
        } else {
            if (isset($this->data['cookie'][$key])) {
                return $this->data['cookie'][$key];
            } else {
                return;
            }
        }
    }

    public function files($key = null)
    {
        if (null == $key) {
            if (isset($this->data['files'])) {
                return $this->data['files'];
            } else {
                return;
            }
        } else {
            if (isset($this->data['files'][$key])) {
                return  $this->data['files'][$key];
            } else {
                return;
            }
        }
    }

    public function file($key = null)
    {
        return $this->files($key);
    }

    public function server($key = null)
    {
        if (null == $key) {
            if (isset($this->data['server'])) {
                return $this->data['server'];
            } else {
                return;
            }
        } else {
            if (isset($this->data['server'][$key])) {
                return  $this->data['server'][$key];
            } else {
                return;
            }
        }
    }

    public function header($key = null)
    {
        if (null == $key) {
            if (isset($this->data['header'])) {
                return $this->data['header'];
            } else {
                return;
            }
        } else {
            if (isset($this->data['header'][$key])) {
                return  $this->data['header'][$key];
            } else {
                return;
            }
        }
    }

    public function all($key = null)
    {
        if (null == $key) {
            if (isset($this->data['all'])) {
                return $this->data['all'];
            } else {
                return;
            }
        } else {
            if (isset($this->data['all'][$key])) {
                return  $this->data['all'][$key];
            } else {
                return;
            }
        }
    }
    
    public function input($key = null) {
    	return $this->all($key);
    }

    public function request($key = null) {
        return $this->all($key);
    }
    
    public function raw() {
    	return $this->data['raw'];
    }

    public function __toString()
    {
        return json_encode([
            'type' => $this->type,
            'map' => $this->map,
            'route' => $this->routeMatchInfo,
            'data' => $this->data,
        ]);
    }
	
    public function setParams()
    {
        if ('cli' === php_sapi_name()) {
            $this->type = 'console';
            global $argv;
            $consoleMap = $argv;
            unset($consoleMap[0]);
            $this->map = implode(' ', $consoleMap);
        } else {
            $qs = '';
            if(isset($_SERVER['QUERY_STRING'])) {
                $qs = $_SERVER['QUERY_STRING'];
            }
            $this->map = str_replace('?'.$qs, '', $_SERVER['REQUEST_URI']);
			
            $this->type = strtolower($_SERVER['REQUEST_METHOD']);
            $this->data['post'] = $_POST;
            $this->data['get'] = $_GET;
//             if ('GET' === $_SERVER['REQUEST_METHOD']) {
//                 $this->data['get'] = $_GET;
//             } else {
//                 $this->data['post'] = $_POST;
//             }
            $this->data['files'] = $_FILES;
            $this->data['server'] = $_SERVER;
            $getHeader = function () {
                $headers = array();
                foreach ($_SERVER as $key => $value) {
                    if (substr($key, 0, 5) === 'HTTP_') {
                        $key = substr($key, 5);
                        $key = strtolower($key);
                        $key = str_replace('_', ' ', $key);
                        $key = ucwords($key);
                        $key = str_replace(' ', '-', $key);
                        $headers[$key] = $value;
                    }
                }

                return $headers;
            };
            $this->data['header'] = $getHeader();
            $this->data['cookie'] = $_COOKIE;
            $this->data['all'] = $_REQUEST;
            $this->data['raw'] =  file_get_contents("php://input");
            $inputArgs = [];
            parse_str($this->data['raw'], $inputArgs);
            $this->data['all'] = array_merge($this->data['all'], $inputArgs);
        }
    }

    public function setSwooleParams($swooleServer)
    {
        if (isset($swooleServer->server['query_string'])) {
            $queryString = $swooleServer->server['query_string'];
        } else {
            $queryString = '';
        }
        $this->map = str_replace('?'.$queryString, '', $swooleServer->server['request_uri']);
        $this->data['all'] = [];
        $this->type = strtolower($swooleServer->server['request_method']);

        if (isset($swooleServer->post)) {
            $this->data['post'] = $swooleServer->post;
            foreach ($this->data['post'] as $k => $v) {
                $this->data['all'][$k] = $v;
            }
        }
        if (isset($swooleServer->get)) {
            $this->data['get'] = $swooleServer->get;
            foreach ($this->data['get'] as $k => $v) {
                $this->data['all'][$k] = $v;
            }
        }
        if (isset($swooleServer->cookie)) {
            $this->data['cookie'] = $swooleServer->cookie;
        }
        if (isset($swooleServer->files)) {
            $this->data['files'] = $swooleServer->files;
        }
        if (isset($swooleServer->header)) {
            $this->data['header'] = $swooleServer->header;
        }
        if (isset($swooleServer->server)) {
            $this->data['server'] = $swooleServer->server;
        }
    }
}
