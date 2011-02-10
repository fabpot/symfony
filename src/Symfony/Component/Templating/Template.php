<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Templating;

/**
 * TODO
 *
 * @author Victor Berchet <victor@suumit.com>
 */
class Template implements TemplateInterface
{
    protected $parameters;

    public function  __construct($name = null, $engine = null)
    {
        $this->parameters = array(
            'name'      => $name,
            'engine'    => $engine,
        );
    }

    public function __toString()
    {
        return json_encode($this->parameters);
    }

    public function getSignature()
    {
        return md5(serialize($this->parameters));
    }

    public function set($name, $value)
    {
        if (array_key_exists($name, $this->parameters)) {
            $this->parameters[$name] = $value;
        } else {
            throw new \RuntimeException(sprintf('The template does not support the "%s" parameter.', $name));
        }
    }

    public function get($name)
    {
        if (array_key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        } else {
            throw new \RuntimeException(sprintf('The template does not support the "%s" parameter.', $name));
        }
    }

    public function all()
    {
        return $this->parameters;
    }

}
