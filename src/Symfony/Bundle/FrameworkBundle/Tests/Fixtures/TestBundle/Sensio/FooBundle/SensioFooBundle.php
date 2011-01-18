<?php

namespace TestBundle\Sensio\FooBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Bundle.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class SensioFooBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'SensioFooBundle';
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }
}
