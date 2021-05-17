<?php

namespace Application\Server\JobServer\Proxy;

class ObjectProxy
{
	public $proxyId;
	public $objectName;
	public $objectArgs;
	public $parentObjectProxy;
	public $isRoot;
	public $callCollection;

	public function __construct($objectName, $objectArgs = null, self $parentObjectProxy = null)
	{
		$this->objectName = $objectName;
		$this->objectArgs = $objectArgs;
		$this->proxyId = spl_object_hash($this);

		if ($parentObjectProxy) {
			$this->isRoot = false;
			$this->parentObjectProxy = $parentObjectProxy;
		} else {
			$this->isRoot = true;
		}
	}

	public function finishProcedure()
	{
		return new ObjectProxyService($this);
	}

	public function __call($method, $args)
	{
		$objectProxy = new self($method, $args, $this);

		$this->callCollection[] = $call = ['proxyId' => $objectProxy->proxyId, 'proxyStruct' => $objectProxy, 'call' => $method, 'args' => $args, 'target' => $this->proxyId];

		return new VirtualStruct($objectProxy);
	}
}
