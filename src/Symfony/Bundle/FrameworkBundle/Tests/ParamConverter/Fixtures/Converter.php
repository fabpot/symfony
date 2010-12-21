<?php

namespace Symfony\Bundle\FrameworkBundle\Tests\ParamConverter\Fixtures;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\ParamConverter\ConverterInterface;

class Converter implements ConverterInterface
{
    public function convert(Request $request, \ReflectionParameter $parameter)
    {
    }

    public function supports(\ReflectionClass $class)
    {
    }
}
