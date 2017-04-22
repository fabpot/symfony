<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests;

use Symfony\Bundle\DoctrineMongoDBBundle\Form\DocumentChoiceField;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\TestDocument;
use Doctrine\ORM\Tools\SchdmaTool;
use Doctrine\Common\Collections\ArrayCollection;

class DocumentChoiceFieldTest extends TestCase
{
    const DOCUMENT_CLASS = 'Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\TestDocument';

    /**
     * @var DocumentManager
     */
    private $dm;

    protected function setUp()
    {
        parent::setUp();
        $this->dm = $this->createTestDocumentManager();
        try {
            $this->dm->getDocumentCollection(self::DOCUMENT_CLASS)->drop();
        } catch (\MongoConnectionException $exception) {
            $this->markTestSkipped('This test requires a working MongoDB connection.');
        }
    }

    protected function persist(array $entities)
    {
        foreach ($entities as $document) {
            $this->dm->persist($document);
        }

        $this->dm->flush();
        // no clear, because entities managed by the choice field must
        // be managed!
    }

    public function testNonRequiredContainsEmptyField()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');

        $this->persist(array($document1, $document2));

        $field = new DocumentChoiceField('name', array(
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'required' => false,
            'property' => 'name'
        ));

        $this->assertEquals(array('' => '', 1 => 'Foo', 2 => 'Bar'), $field->getOtherChoices());
    }

//    public function testSetDataToUninitializedDocumentWithNonRequired()
//    {
//        $document1 = new TestDocument(1, 'Foo');
//        $document2 = new TestDocument(2, 'Bar');
//
//        $this->persist(array($document1, $document2));
//
//        $field = new DocumentChoiceField('name', array(
//            'dm' => $this->dm,
//            'class' => self::DOCUMENT_CLASS,
//            'required' => false,
//            'property' => 'name'
//        ));
//
//        $this->assertEquals(array('' => '', 1 => 'Foo', 2 => 'Bar'), $field->getOtherChoices());
//
//    }

    /**
     * @expectedException Symfony\Component\Form\Exception\InvalidOptionsException
     */
    public function testConfigureQueryBuilderWithNonQueryBuilderAndNonClosure()
    {
        $field = new DocumentChoiceField('name', array(
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'query_builder' => new \stdClass(),
        ));
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\InvalidOptionsException
     */
    public function testConfigureQueryBuilderWithClosureReturningNonQueryBuilder()
    {
        $field = new DocumentChoiceField('name', array(
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'query_builder' => function () {
                return new \stdClass();
            },
        ));

        $field->submit('2');
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\FormException
     */
    public function testChoicesMustBeManaged()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');

        // no persist here!

        $field = new DocumentChoiceField('name', array(
            'multiple' => false,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'choices' => array($document1, $document2),
            'property' => 'name',
        ));
    }

    public function testSetDataSingle_null()
    {
        $field = new DocumentChoiceField('name', array(
            'multiple' => false,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->setData(null);

        $this->assertEquals(null, $field->getData());
        $this->assertEquals('', $field->getDisplayedData());
    }

    public function testSetDataMultiple_null()
    {
        $field = new DocumentChoiceField('name', array(
            'multiple' => true,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->setData(null);

        $this->assertEquals(null, $field->getData());
        $this->assertEquals(array(), $field->getDisplayedData());
    }

    public function testSubmitSingle_null()
    {
        $field = new DocumentChoiceField('name', array(
            'multiple' => false,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->submit(null);

        $this->assertEquals(null, $field->getData());
        $this->assertEquals('', $field->getDisplayedData());
    }

    public function testSubmitMultiple_null()
    {
        $field = new DocumentChoiceField('name', array(
            'multiple' => true,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
        ));
        $field->submit(null);

        $this->assertEquals(new ArrayCollection(), $field->getData());
        $this->assertEquals(array(), $field->getDisplayedData());
    }

    public function testSubmitSingleNonExpanded_singleIdentifier()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');

        $this->persist(array($document1, $document2));

        $field = new DocumentChoiceField('name', array(
            'multiple' => false,
            'expanded' => false,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $field->submit('2');

        $this->assertTrue($field->isTransformationSuccessful());
        $this->assertEquals($document2, $field->getData());
        $this->assertEquals(2, $field->getDisplayedData());
    }

    public function testSubmitMultipleNonExpanded_singleIdentifier()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');
        $document3 = new TestDocument(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = new DocumentChoiceField('name', array(
            'multiple' => true,
            'expanded' => false,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $field->submit(array('1', '3'));

        $expected = new ArrayCollection(array($document1, $document3));

        $this->assertTrue($field->isTransformationSuccessful());
        $this->assertEquals($expected, $field->getData());
        $this->assertEquals(array(1, 3), $field->getDisplayedData());
    }

    public function testSubmitMultipleNonExpanded_singleIdentifier_existingData()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');
        $document3 = new TestDocument(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = new DocumentChoiceField('name', array(
            'multiple' => true,
            'expanded' => false,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $existing = new ArrayCollection(array($document2));

        $field->setData($existing);
        $field->submit(array('1', '3'));

        // entry with index 0 was rdmoved
        $expected = new ArrayCollection(array(1 => $document1, 2 => $document3));

        $this->assertTrue($field->isTransformationSuccessful());
        $this->assertEquals($expected, $field->getData());
        // same object still, useful if it is a PersistentCollection
        $this->assertSame($existing, $field->getData());
        $this->assertEquals(array(1, 3), $field->getDisplayedData());
    }

    public function testSubmitSingleExpanded()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');

        $this->persist(array($document1, $document2));

        $field = new DocumentChoiceField('name', array(
            'multiple' => false,
            'expanded' => true,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $field->submit('2');

        $this->assertTrue($field->isTransformationSuccessful());
        $this->assertEquals($document2, $field->getData());
        $this->assertSame(false, $field['1']->getData());
        $this->assertSame(true, $field['2']->getData());
        $this->assertSame('', $field['1']->getDisplayedData());
        $this->assertSame('1', $field['2']->getDisplayedData());
        $this->assertSame(array('1' => '', '2' => '1'), $field->getDisplayedData());
    }

    public function testSubmitMultipleExpanded()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');
        $document3 = new TestDocument(3, 'Bar');

        $this->persist(array($document1, $document2, $document3));

        $field = new DocumentChoiceField('name', array(
            'multiple' => true,
            'expanded' => true,
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'property' => 'name',
        ));

        $field->submit(array('1' => '1', '3' => '3'));

        $expected = new ArrayCollection(array($document1, $document3));

        $this->assertTrue($field->isTransformationSuccessful());
        $this->assertEquals($expected, $field->getData());
        $this->assertSame(true, $field['1']->getData());
        $this->assertSame(false, $field['2']->getData());
        $this->assertSame(true, $field['3']->getData());
        $this->assertSame('1', $field['1']->getDisplayedData());
        $this->assertSame('', $field['2']->getDisplayedData());
        $this->assertSame('1', $field['3']->getDisplayedData());
        $this->assertSame(array('1' => '1', '2' => '', '3' => '1'), $field->getDisplayedData());
    }

    public function testOverrideChoices()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');
        $document3 = new TestDocument(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = new DocumentChoiceField('name', array(
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            // not all persisted entities should be displayed
            'choices' => array($document1, $document2),
            'property' => 'name',
        ));

        $field->submit('2');

        $this->assertEquals(array(1 => 'Foo', 2 => 'Bar'), $field->getOtherChoices());
        $this->assertTrue($field->isTransformationSuccessful());
        $this->assertEquals($document2, $field->getData());
        $this->assertEquals(2, $field->getDisplayedData());
    }

    public function testDisallowChoicesThatAreNotIncluded_choices_singleIdentifier()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');
        $document3 = new TestDocument(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = new DocumentChoiceField('name', array(
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'choices' => array($document1, $document2),
            'property' => 'name',
        ));

        $field->submit('3');

        $this->assertFalse($field->isTransformationSuccessful());
        $this->assertNull($field->getData());
    }

    public function testDisallowChoicesThatAreNotIncluded_queryBuilder_singleIdentifier()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');
        $document3 = new TestDocument(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $repository = $this->dm->getRepository(self::DOCUMENT_CLASS);

        $field = new DocumentChoiceField('name', array(
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'query_builder' => $repository->createQueryBuilder()
                ->field('id')->in(array(1, 2)),
            'property' => 'name',
        ));

        $field->submit('3');

        $this->assertFalse($field->isTransformationSuccessful());
        $this->assertNull($field->getData());
    }

    public function testDisallowChoicesThatAreNotIncluded_queryBuilderAsClosure_singleIdentifier()
    {
        $document1 = new TestDocument(1, 'Foo');
        $document2 = new TestDocument(2, 'Bar');
        $document3 = new TestDocument(3, 'Baz');

        $this->persist(array($document1, $document2, $document3));

        $field = new DocumentChoiceField('name', array(
            'dm' => $this->dm,
            'class' => self::DOCUMENT_CLASS,
            'query_builder' => function ($repository) {
                return $repository->createQueryBuilder('e')
                        ->field('id')->in(array(1, 2));
            },
            'property' => 'name',
        ));

        $field->submit('3');

        $this->assertFalse($field->isTransformationSuccessful());
        $this->assertNull($field->getData());
    }
}