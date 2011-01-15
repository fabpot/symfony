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
 * A field for entering a password.
 *
 * @author Bernhard Schussek <bernhard.schussek@symfony-project.com>
 */
class PasswordField extends TextField
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addOption('always_empty', true);

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayedData()
    {
        return $this->getOption('always_empty') || !$this->isBound()
                ? ''
                : parent::getDisplayedData();
    }
}