<?php

namespace Application\Server\JobServer\Proxy;

class ObjectProxyService
{
    public $objectProxy;
    public $returns;
    public $error;

    public function __construct($object)
    {
        try {
            if ($object instanceof VirtualStruct) {
                $this->objectProxy = $objectProxy = $object->getObjectProxy();
            } elseif ($object instanceof ObjectProxy) {
                $this->objectProxy = $objectProxy = $object;
            } else {
                throw new \Exception("Invalid object: ".print_r($object, true)."。\n");
            }

            $rootObjectProxy = self::getRoot($objectProxy);

            $rootAbsoluteObject = new AbsoluteObject($rootObjectProxy->objectName, $this->checkArgs($rootObjectProxy->objectArgs));
            $rootInstance = $rootAbsoluteObject->getInstance();
            $this->returns = [$rootObjectProxy->proxyId => $rootInstance];
            if ($rootObjectProxy->callCollection) {
                foreach ($rootObjectProxy->callCollection as $k => $call) {
                    if (is_callable([$this->returns[$call['target']], $call['call']])) {
                        $this->returns[ $call['proxyId']] = $rootObjectProxy->callCollection[$k]['returnResult'] = call_user_func_array([$this->returns[$call['target']], $call['call']], $this->checkArgs($call['args']));
                        $rootObjectProxy->callCollection[$k]['return'] = true;
                        $this->processStruct($call['proxyStruct']);
                    } else {
                        throw new \Exception(sprintf("无法调用对象 %s 的 %s 方法。\n", get_class($rootInstance), $call['call']));
                    }
                }
            }

            $this->processMethod($objectProxy);
        } catch (\Exception $e) {
            $this->error = $e;
        }
    }

    public function getResult()
    {
        if (isset($this->error)) {
            return $this->error;
        }

        return $this->returns[$this->objectProxy->proxyId];
    }

    public static function getRoot(ObjectProxy $objectProxy)
    {
        if (isset($objectProxy->parentObjectProxy)) {
            return self::getRoot($objectProxy->parentObjectProxy);
        } else {
            return $objectProxy;
        }
    }

    public function processStruct($struct)
    {
        if (!isset($struct->callCollection)) {
            return;
        }

        foreach ($struct->callCollection as $k => $call) {
            if (isset($struct->callCollection[$k]['return'])) {
                continue;
            }

            if (isset($this->returns[$call['target']])) {
                if (is_callable([$this->returns[$call['target']], $call['call']])) {
                    $this->returns[ $call['proxyId']] = $struct->callCollection[$k]['returnResult'] = call_user_func_array([$this->returns[$call['target']], $call['call']], $this->checkArgs($call['args']));

                    $struct->callCollection[$k]['return'] = true;
                } else {
                    throw new \Exception(sprintf("无法调用对象 %s 的 %s 方法。\n", get_class($this->returns[$call['target']]), $call['call']));
                }
            }
        }
    }

    public function checkArgs($args = null)
    {
        if (!isset($args)) {
            return;
        }
        foreach ($args as $k => $arg) {
            if (!isset($arg)) {
                continue;
            }

            if ($arg instanceof VirtualStruct) {
                $argProxy = $arg->getObjectProxy();                
            } elseif ($arg instanceof ObjectProxy) {
                $argProxy = $arg;
            } else {
                continue;
            }
            if (isset($this->returns[$argProxy->proxyId])) {
                $args[$k] = $this->returns[$argProxy->proxyId];
            }
        }

        return $args;
    }

    public function processMethod(ObjectProxy $objectProxy)
    {
        if (false === $objectProxy->isRoot) {
            if (isset($objectProxy->parentObjectProxy)) {
                if (false === $objectProxy->parentObjectProxy->isRoot) {
                    $this->processMethod($objectProxy->parentObjectProxy);
                } else {
                    if (isset($objectProxy->callCollection)) {
                        foreach ($objectProxy->callCollection as $k => $call) {
                            if (!isset($objectProxy->callCollection[$k]['return'])) {
                                if (isset($this->returns[$call['target']])) {
                                    if (is_callable([$this->returns[$call['target']], $call['call']])) {
                                        $this->returns[ $call['proxyId']] = $objectProxy->callCollection[$k]['returnResult'] = call_user_func_array([$this->returns[$call['target']], $call['call']], $this->checkArgs($call['args']));

                                        $objectProxy->callCollection[$k]['return'] = true;

                                        $this->processStruct($call['proxyStruct']);
                                    } else {
                                        throw new \Exception(sprintf("无法调用对象 %s 的 %s 方法。\n", get_class($this->returns[$call['target']]), $call['call']));
                                    }
                                }
                            }
                        }
                    }
                    $objectProxy->isRoot = true;
                    $this->processMethod($this->objectProxy);
                }
            }
        }
    }
}
