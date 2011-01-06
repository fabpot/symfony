<?php

namespace Symfony\Test\Component\Form;

use Symfony\Component\Form\HiddenField;

class HiddenFieldTest extends \PHPUnit_Framework_TestCase
{
    protected $field;

    protected function setUp()
    {
        $this->field = new HiddenField('name');
    }

    public function testIsHidden()
    {
        $this->assertTrue($this->field->isHidden());
    }
}