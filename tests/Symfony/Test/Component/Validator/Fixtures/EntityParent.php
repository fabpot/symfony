<?php

namespace Symfony\Test\Component\Validator\Fixtures;

class EntityParent
{
    protected $firstName;
    private $internal;

    /**
     * @validation:NotNull
     */
    protected $other;
}