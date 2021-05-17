<?php

namespace Application\Server\JobServer\Proxy;

class Transporter
{
    public static function encode($object)
    {
        return base64_encode(serialize($object));
    }

    public static function decode($encodeObject)
    {
        return unserialize(base64_decode($encodeObject));
    }
}