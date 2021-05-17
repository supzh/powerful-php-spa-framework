<?php

namespace Application\Server\JobServer\Proxy;

class VirtualStruct
{
    public $objectProxy;

    public function __construct(ObjectProxy $objectProxy)
    {
        $this->objectProxy = $objectProxy;
    }

    public function __call($virtualCall, $virtualArgs)
    {
        return call_user_func_array([$this->objectProxy, $virtualCall], $virtualArgs);
    }

    public function finishProcedure()
    {
        return new ObjectProxyService($this);
    }

    public function getObjectProxy()
    {
        return $this->objectProxy;
    }
}
