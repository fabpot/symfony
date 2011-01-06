<?php

namespace Symfony\Test\Component\Validator\Fixtures;

use Symfony\Component\Validator\Constraint;

class FailingConstraint extends Constraint
{
    public $message = '';
}