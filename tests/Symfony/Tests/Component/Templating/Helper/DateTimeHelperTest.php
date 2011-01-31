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
use Symfony\Bundle\FrameworkBundle\Templating\Helper\DateTimeHelper;

class DateTimeHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testLocale()
    {
        $session = $this->getMock('Symfony\\Component\\HttpFoundation\\Session', array('getLocale'), array(), 'Session', false);

        $session->expects($this->any())
            ->method('getLocale')
            ->will($this->returnValue('fr'));

        $helper = new DateTimeHelper($session);

        $datetime = new \DateTime();
        $datetime->setDate(1981, 11, 30);
        $datetime->setTime(2, 0, 0);
        $datetime->setTimezone(new \DateTimeZone('Europe/Paris'));

        // check convertor
        $this->assertEquals(375930000, $helper->getTimestamp($datetime));

        // check default method
        $this->assertEquals('30 nov. 1981', $helper->formatDate($datetime));
        $this->assertEquals('02:00:00', $helper->formatTime($datetime));
        $this->assertEquals('30 nov. 1981 02:00:00', $helper->formatDateTime($datetime));

        // custom format
        $this->assertEquals('30 nov. 1981 ap. J.-C.', $helper->format($datetime, 'dd MMM Y G'));

    }
}