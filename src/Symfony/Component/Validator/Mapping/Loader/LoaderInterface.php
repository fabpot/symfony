<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\Validator\Mapping\Loader;

use Symfony\Component\Validator\Mapping\ClassMetadata;

interface LoaderInterface
{
    /**
     * Load a Class Metadata.
     *
     * @param ClassMetadata $metadata A metadata
     *
     * @return boolean
     */
    function loadClassMetadata(ClassMetadata $metadata);
}