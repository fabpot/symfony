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
 * A field for entering a birthday date
 *
 * This field is a preconfigured DateField with allowed years between the
 * current year and 120 years in the past.
 *
 * @author Bernhard Schussek <bernhard.schussek@symfony-project.com>
 */
class BirthdayField extends DateField
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $currentYear = date('Y');

        $this->addOption('years', range($currentYear-120, $currentYear));

        parent::configure();
    }
}
