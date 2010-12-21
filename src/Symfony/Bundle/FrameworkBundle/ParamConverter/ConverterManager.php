<?php

namespace Symfony\Bundle\FrameworkBundle\ParamConverter;

use Symfony\Bundle\FrameworkBundle\ParamConverter\Converter\ConverterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * ConverterManager
 * Keeps track of converters
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Henrik Bjornskov <hb@peytz.dk>
 */
class ConverterManager
{
    /**
     * @var array
     */
    protected $converters = array();

    /**
     * Cycles through all converters and if a converter supports the class it applies
     * the converter. If no converter matches the ReflectionParameters::getClass() value
     * a InvalidArgumentException is thrown.
     *
     * @param  Request $request
     * @param  array   $parameters An array of ReflectionParameter objects
     * @throws InvalidArgumentException
     */
    public function apply(Request $request, \ReflectionParameter $parameter)
    {
        $converted = false;
        $converters = $this->all();

        foreach ($this->all() as $converter) {
            if ($converter->supports($parameter->getClass())) {
                $converter->convert($request, $parameter);
                $converted = true;
            }
        }

        if (false == $converted) {
            throw new \InvalidArgumentException(sprintf('Could not convert "%s" into an instance of "%"', $parameter->getName(), $parameter->getClass()->getName()));
        }
    }

    /**
     * Add a converter
     *
     * @param ConverterInterface $converter
     * @param integer            $prioriry = 0
     */
    public function add(ConverterInterface $converter, $priority = 0)
    {
        if (!isset($this->converters[$priority])) {
            $this->converters[$priority] = array();
        }

        $this->converters[$priority][] = $converter;
    }

    /**
     * Returns all converters sorted after their priorities
     *
     * @return array
     */
    public function all()
    {
        $all = $this->converters;
        $converters = array();
        krsort($this->converters);

        foreach ($all as $c) {
            $converters = array_merge($converters, $c);
        }

        return $converters;
    }
}
