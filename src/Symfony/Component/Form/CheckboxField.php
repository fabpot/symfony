<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\Form;

/**
 * A checkbox field for selecting boolean values.
 *
 * @author Bernhard Schussek <bernhard.schussek@symfony-project.com>
 */
class CheckboxField extends ToggleField
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addOption('value', '1');

        parent::configure();
    }
}