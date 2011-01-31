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

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\LocaleHelper;
use Symfony\Component\Locale\Locale;

class LocaleTest extends \PHPUnit_Framework_TestCase
{
    public function testLocale()
    {
        $session = $this->getMock('Symfony\\Component\\HttpFoundation\\Session', array('getLocale'), array(), 'Session', false);

        $session->expects($this->any())
            ->method('getLocale')
            ->will($this->returnValue('fr'));

        $helper = new LocaleHelper($session);

        $this->assertEquals('français', $helper->language('fr'));
        $this->assertEquals('France', $helper->country('FR'));
        $this->assertEquals('français', $helper->locale('fr'));
        $this->assertEquals('français (Canada)', $helper->locale('fr_CA'));

        $this->assertEquals('French', $helper->language('fr', 'en'));
        $this->assertEquals('France', $helper->country('FR', 'en'));
        $this->assertEquals('French', $helper->locale('fr', 'en'));
        $this->assertEquals('French (Canada)', $helper->locale('fr_CA', 'en'));

        // for now these tests are commented as the RessourceBundle always fallback to the
        // system value ... don't know how the framework should behave.
        
//        $this->assertEquals('', $helper->language('fr', 'fake'));
//        $this->assertEquals('', $helper->country('FR', 'fake'));
//        $this->assertEquals('', $helper->locale('fr', 'fake'));
//        $this->assertEquals('', $helper->locale('fr_CA', 'fake'));

    }
}
