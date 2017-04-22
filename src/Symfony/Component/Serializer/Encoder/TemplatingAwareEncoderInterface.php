<?php

namespace Symfony\Component\Serializer\Encoder;

use Symfony\Component\Templating\EngineInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Defines the interface of templating aware encoders
 *
 * @author Lukas Smith <smith@pooteeweet.org>
 */
interface TemplatingAwareEncoderInterface extends EncoderInterface
{
    /**
     * Sets the Templating object
     *
     * @param EngineInterface $templating
     */
    function setTemplating(EngineInterface $templating);

    /**
     * Gets the Templating object
     *
     * @return SerializerInterface
     */
    function getTemplating();

    /**
     * Sets the template
     *
     * @param string $template
     */
    function setTemplate($template);

    /**
     * Gets the template
     *
     * @return string
     */
    function getTemplate();
}
