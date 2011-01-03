<?php

namespace Symfony\Tests\Component\Validator\Fixtures;

require_once __DIR__ . '/EntityInterface.php';
require_once __DIR__ . '/Entity.php';

class EntityWithChild implements EntityInterface
{
    protected $child;

    public function __construct()
    {
        $this->child = new Entity();
    }
}
