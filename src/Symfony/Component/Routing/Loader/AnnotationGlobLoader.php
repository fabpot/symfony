<?php

namespace Symfony\Component\Routing\Loader;

use Symfony\Component\Routing\RouteCollection;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * AnnotationGlobLoader loads routing information from annotations set
 * on PHP classes and methods.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class AnnotationGlobLoader extends AnnotationDirectoryLoader
{
    /**
     * Loads from annotations from a directory glob pattern
     *
     * @param string $resource A directory glob pattern containing "*"
     * @param string $type     The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When route can't be parsed
     */
    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();
        foreach ($this->getAbsolutePaths($resource) as $resource) {
            $collection->addCollection(parent::load($resource, $type));
        }

        return $collection;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && false !== strpos($resource, '*') && (!$type || $type === $this->getType());
    }

    protected function getAbsolutePaths($glob)
    {
        $dirs = array();
        foreach ($this->paths as $path) {
            if (false !== $d = glob($path.DIRECTORY_SEPARATOR.$glob, GLOB_ONLYDIR | GLOB_BRACE)) {
                $dirs = array_merge($dirs, $d);
            }
        }

        return $dirs;
    }
}
