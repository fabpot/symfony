<?php

namespace Symfony\Test\Component\Form\Fixtures;

use Symfony\Component\Form\Field;

class InvalidField extends Field
{
    public function isValid()
    {
        return false;
    }

    public function render(array $attributes = array())
    {
    }
}