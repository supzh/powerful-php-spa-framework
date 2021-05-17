<?php

namespace Application;

class RouteMiddleware
{
    protected $routeType;
    protected $routeParamKey;
    protected $routeTargetCallable;

    public function __construct($type, $paramKey, $targetCallable)
    {
        $this->routeType = $type;
        $this->routeParamKey = $paramKey;
        $this->routeTargetCallable = $targetCallable;
    }

    public function middleware($callable)
    {
        if(is_string($this->routeType)) {
            Route::$routesMiddleware[strtolower($this->routeType)][$this->routeParamKey] = $callable;
        } elseif(is_array($this->routeType)) {
            foreach( $this->routeType as $typeVal) {
                Route::$routesMiddleware[strtolower($typeVal)][$this->routeParamKey] = $callable;
            }
        }
    }
}