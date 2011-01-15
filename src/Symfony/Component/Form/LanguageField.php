<?php

namespace Symfony\Component\Form;

use Symfony\Component\Locale\Locale;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A field for selecting from a list of languages
 *
 * @author Bernhard Schussek <bernhard.schussek@symfony-project.com>
 */
class LanguageField extends ChoiceField
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addOption('choices', Locale::getDisplayLanguages($this->locale));

        parent::configure();
    }
}