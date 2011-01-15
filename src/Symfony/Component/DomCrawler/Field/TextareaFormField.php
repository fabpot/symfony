<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DomCrawler\Field;

/**
 * TextareaFormField represents a textarea form field (an HTML textarea tag).
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class TextareaFormField extends FormField
{
    /**
     * Initializes the form field.
     *
     * @throws \LogicException When node type is incorrect
     */
    protected function initialize()
    {
        if ('textarea' != $this->node->nodeName) {
            throw new \LogicException(sprintf('A TextareaFormField can only be created from a textarea tag (%s given).', $this->node->nodeName));
        }

        $this->value = null;
        foreach ($this->node->childNodes as $node) {
            $this->value .= $this->document->saveXML($node);
        }
    }
}
