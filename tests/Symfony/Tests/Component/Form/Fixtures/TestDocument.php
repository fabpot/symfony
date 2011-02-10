<?php

namespace Symfony\Tests\Component\Form\Fixtures;

/** @Document */
class TestDocument
{
    /** @Id(strategy="none") */
    protected $id;

    /** @String */
    public $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}