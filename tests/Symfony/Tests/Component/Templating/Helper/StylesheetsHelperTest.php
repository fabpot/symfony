<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Templating\Helper;

use Symfony\Component\Templating\Helper\AssetsHelper;
use Symfony\Component\Templating\Helper\StylesheetsHelper;
use Symfony\Component\Templating\Loader\FilesystemLoader;

class StylesheetsHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $assetHelper = new AssetsHelper();
        $helper = new StylesheetsHelper($assetHelper);
        $helper->add('foo');
        $this->assertEquals(array(0 => array('/foo' => array())), $helper->get(), '->add() adds a Stylesheet');
        $helper->add('/foo');
        $this->assertEquals(array(0 => array('/foo' => array())), $helper->get(), '->add() does not add the same Stylesheet twice');
        $helper = new StylesheetsHelper($assetHelper);
        $helper->add('foo', array(), 1);
        $this->assertEquals(array(1 => array('/foo' => array())), $helper->get(), '->add() adds a Stylesheet at level 1');
        $helper->add('bar', array(), 2);
        $this->assertEquals(array(1 => array('/foo' => array()), 2 => array('/bar' => array())), $helper->get(), '->add() adds a Stylesheet at level 2');
        $helper = new StylesheetsHelper($assetHelper);
        $assetHelper->setBaseURLs('http://assets.example.com/');
        $helper->add('foo');
        $this->assertEquals(array(0 => array('http://assets.example.com/foo' => array())), $helper->get(), '->add() converts the Stylesheet to a public path');
    }

    public function testMagicToString()
    {
        $assetHelper = new AssetsHelper();
        $assetHelper->setBaseURLs('');
        $helper = new StylesheetsHelper($assetHelper);
        $helper->add('foo', array('media' => 'ba>'));
        $this->assertEquals('<link href="/foo" rel="stylesheet" type="text/css" media="ba&gt;" />'."\n", $helper->__toString(), '->__toString() converts the stylesheet configuration to HTML');
    }
}
