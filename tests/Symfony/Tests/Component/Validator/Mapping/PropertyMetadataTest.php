<?php

namespace Symfony\Tests\Component\Validator\Mapping;

require_once __DIR__.'/../Fixtures/Entity.php';

use Symfony\Component\Validator\Mapping\PropertyMetadata;
use Symfony\Tests\Component\Validator\Fixtures\Entity;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class PropertyMetadataTest extends \PHPUnit_Framework_TestCase
{
    const CLASSNAME = 'Symfony\Tests\Component\Validator\Fixtures\Entity';

    public function testInvalidPropertyName()
    {
        $this->setExpectedException('Symfony\Component\Validator\Exception\ValidatorException');

        new PropertyMetadata(self::CLASSNAME, 'foobar');
    }

    public function testGetValueFromPrivateProperty()
    {
        $entity = new Entity('foobar');
        $metadata = new PropertyMetadata(self::CLASSNAME, 'internal');

        $this->assertEquals('foobar', $metadata->getValue($entity));
    }
}

