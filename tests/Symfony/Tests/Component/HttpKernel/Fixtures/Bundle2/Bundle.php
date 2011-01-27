<?php
namespace Bundle2;

class Bundle extends \Symfony\Component\HttpKernel\Bundle\Bundle
{
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }

    public function getPriority()
    {
        return 1;
    }
}