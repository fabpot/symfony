<?php

namespace Symfony\Component\Serializer\Encoder;

use Symfony\Component\Serializer\SerializerInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Defines the interface of encoders
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
interface EncoderInterface
{
    function encode($data, $format);
    function decode($data, $format);
    function setSerializer(SerializerInterface $serializer);
    function getSerializer();
}
