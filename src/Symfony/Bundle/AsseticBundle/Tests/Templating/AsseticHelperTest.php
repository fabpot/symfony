<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Tests\Templating;

use Symfony\Bundle\AsseticBundle\Templating\AsseticHelper;

class AsseticHelperTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Assetic\\AssetManager')) {
            $this->markTestSkipped('Assetic is not available.');
        }
    }

    public function testInterface()
    {
        $factory = $this->getMockBuilder('Assetic\\Factory\\AssetFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $helper = new AsseticHelper($factory);

        $this->assertInstanceOf('Symfony\\Component\\Templating\\Helper\\HelperInterface', $helper);
    }

    /**
     * @dataProvider getDebugModesAndCounts
     */
    public function testUrlsReturnType($debug, $nb)
    {
        $factory = $this->getMockBuilder('Assetic\\Factory\\AssetFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $factory->expects($this->once())
            ->method('createAsset')
            ->with(array('js/jquery.js', 'js/jquery.plugin.js'), array(), null)
            ->will($this->returnValue($asset));

        $helper = new AsseticHelper($factory, $debug);
        $urls = $helper->urls(array('js/jquery.js', 'js/jquery.plugin.js'));

        $this->assertInternalType('array', $urls, '->urls() returns an array');
        $this->assertEquals($nb, count($urls));
    }

    public function getDebugModes()
    {
        return array(
            array(true, 2),
            array(false, 1),
        );
    }
}
