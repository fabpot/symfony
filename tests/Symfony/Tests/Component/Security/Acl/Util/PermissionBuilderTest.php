<?php

namespace Symfony\Tests\Component\Security\Acl\Util;

use Symfony\Component\Security\Acl\Util\PermissionBuilder;

class PermissionBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider getInvalidConstructorData
     */
    public function testConstructorWithNonInteger($invalidMask)
    {
        new PermissionBuilder($invalidMask);
    }
    
    public function getInvalidConstructorData()
    {
        return array(
            array(234.463),
            array('asdgasdf'),
            array(array()),
            array(new \stdClass()),
        );
    }
    
    public function testConstructorWithoutArguments()
    {
        $builder = new PermissionBuilder();
        
        $this->assertEquals(0, $builder->getMask());
    }
    
    public function testConstructor()
    {
        $builder = new PermissionBuilder(123456);
        
        $this->assertEquals(123456, $builder->getMask());
    }
    
    public function testAddAndRemove()
    {
        $builder = new PermissionBuilder();
        
        $builder
            ->add('view')
            ->add('eDiT')
            ->add('ownEr')
        ;
        $mask = $builder->getMask();
        
        $this->assertEquals(PermissionBuilder::MASK_VIEW, $mask & PermissionBuilder::MASK_VIEW);
        $this->assertEquals(PermissionBuilder::MASK_EDIT, $mask & PermissionBuilder::MASK_EDIT);
        $this->assertEquals(PermissionBuilder::MASK_OWNER, $mask & PermissionBuilder::MASK_OWNER);
        $this->assertEquals(0, $mask & PermissionBuilder::MASK_ADMINISTER);
        $this->assertEquals(0, $mask & PermissionBuilder::MASK_CREATE);
        $this->assertEquals(0, $mask & PermissionBuilder::MASK_DELETE);
        $this->assertEquals(0, $mask & PermissionBuilder::MASK_UNDELETE);
        
        $builder->remove('edit')->remove('OWner');
        $mask = $builder->getMask();
        $this->assertEquals(0, $mask & PermissionBuilder::MASK_EDIT);
        $this->assertEquals(0, $mask & PermissionBuilder::MASK_OWNER);
        $this->assertEquals(PermissionBuilder::MASK_VIEW, $mask & PermissionBuilder::MASK_VIEW);
    }
    
    public function testGetPattern()
    {
        $builder = new PermissionBuilder;
        $this->assertEquals(PermissionBuilder::ALL_OFF, $builder->getPattern());
        
        $builder->add('view');
        $this->assertEquals(str_repeat('.', 31).'V', $builder->getPattern());
        
        $builder->add('owner');
        $this->assertEquals(str_repeat('.', 25).'O.....V', $builder->getPattern());
        
        $builder->add(1 << 10);
        $this->assertEquals(str_repeat('.', 21).PermissionBuilder::ON.'...O.....V', $builder->getPattern());
    }
    
    public function testReset()
    {
        $builder = new PermissionBuilder();
        $this->assertEquals(0, $builder->getMask());
        
        $builder->add('view');
        $this->assertTrue($builder->getMask() > 0);
        
        $builder->reset();
        $this->assertEquals(0, $builder->getMask());
    }
}