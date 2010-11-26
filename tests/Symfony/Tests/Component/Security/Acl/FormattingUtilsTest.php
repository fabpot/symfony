<?php

namespace Symfony\Tests\Component\Security\Acl;

use Symfony\Component\Security\Acl\FormattingUtils;

class FormattingUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getFormattingTestData
     */
    public function testStaticGetTextualRepresentation($mask, $output)
    {
        $this->assertEquals($output, FormattingUtils::getTextualRepresentation($mask));
    }
    
    public function getFormattingTestData()
    {
        return array(
            array(bindec('1000'), str_repeat('.', 28).'*...'),
            array(bindec(str_repeat('10', 10)), str_repeat('.', 12).str_repeat('*.', 10)),
            array(bindec('0001'), str_repeat('.', 31).'*'),
            array(bindec(str_repeat('1', 32)), str_repeat('*', 32)),
        );
    }
}