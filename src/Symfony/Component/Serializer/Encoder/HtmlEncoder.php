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
 * Encodes HTML data
 *
 * @author Lukas Smith <smith@pooteeweet.org>
 */
class HtmlEncoder extends AbstractEncoder implements TemplatingAwareEncoderInterface
{
    protected $templating;
    protected $template;

    /**
     * {@inheritdoc}
     */
    public function setTemplating(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplating()
    {
        return $this->templating;
    }

    /**
     * {@inheritdoc}
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format)
    {
        if (null === $this->template) {
            throw new \Exception('TODO find something smart to do here');
        }

        return $this->templating->render($this->template, (array)$data);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format)
    {
        $xml = simplexml_load_string($data);
        if (!$xml->count()) {
            return (string) $xml;
        }
        return $this->parseXml($xml);
    }
}
