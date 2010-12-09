<?php

namespace Symfony\Tests\Component\Form;

require_once __DIR__ . '/Fixtures/Author.php';
require_once __DIR__ . '/Fixtures/TestField.php';
require_once __DIR__ . '/Fixtures/TestFieldGroup.php';

use Symfony\Component\Form\Field;
use Symfony\Component\Form\FieldError;
use Symfony\Component\Form\FieldInterface;
use Symfony\Component\Form\FieldGroup;
use Symfony\Component\Form\PropertyPath;
use Symfony\Component\Form\CollectionField;
use Symfony\Tests\Component\Form\Fixtures\Author;
use Symfony\Tests\Component\Form\Fixtures\TestField;
use Symfony\Tests\Component\Form\Fixtures\TestFieldGroup;


abstract class FieldGroupTest_Field extends TestField
{
    public $locales = array();

    public function setLocale($locale)
    {
        $this->locales[] = $locale;
    }
}


class FieldGroupTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportsArrayAccess()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createMockField('firstName'));
        $this->assertEquals($group->get('firstName'), $group['firstName']);
        $this->assertTrue(isset($group['firstName']));
    }

    public function testSupportsUnset()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createMockField('firstName'));
        unset($group['firstName']);
        $this->assertFalse(isset($group['firstName']));
    }

    public function testDoesNotSupportAddingFields()
    {
        $group = new TestFieldGroup('author');
        $this->setExpectedException('LogicException');
        $group[] = $this->createMockField('lastName');
    }

    public function testSupportsCountable()
    {
        $group = new TestFieldGroup('group');
        $group->add($this->createMockField('firstName'));
        $group->add($this->createMockField('lastName'));
        $this->assertEquals(2, count($group));

        $group->add($this->createMockField('australian'));
        $this->assertEquals(3, count($group));
    }

    public function testSupportsIterable()
    {
        $group = new TestFieldGroup('group');
        $group->add($field1 = $this->createMockField('field1'));
        $group->add($field2 = $this->createMockField('field2'));
        $group->add($field3 = $this->createMockField('field3'));

        $expected = array(
            'field1' => $field1,
            'field2' => $field2,
            'field3' => $field3,
        );

        $this->assertEquals($expected, iterator_to_array($group));
    }

    public function testIsBound()
    {
        $group = new TestFieldGroup('author');
        $this->assertFalse($group->isBound());
        $group->bind(array('firstName' => 'Bernhard'));
        $this->assertTrue($group->isBound());
    }

    public function testValidIfAllFieldsAreValid()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createValidMockField('firstName'));
        $group->add($this->createValidMockField('lastName'));

        $group->bind(array('firstName' => 'Bernhard', 'lastName' => 'Potencier'));

        $this->assertTrue($group->isValid());
    }

    public function testInvalidIfFieldIsInvalid()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createInvalidMockField('firstName'));
        $group->add($this->createValidMockField('lastName'));

        $group->bind(array('firstName' => 'Bernhard', 'lastName' => 'Potencier'));

        $this->assertFalse($group->isValid());
    }

    public function testInvalidIfBoundWithExtraFields()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createValidMockField('firstName'));
        $group->add($this->createValidMockField('lastName'));

        $group->bind(array('foo' => 'bar', 'firstName' => 'Bernhard', 'lastName' => 'Potencier'));

        $this->assertTrue($group->isBoundWithExtraFields());
    }

    public function testHasNoErrorsIfOnlyFieldHasErrors()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createInvalidMockField('firstName'));

        $group->bind(array('firstName' => 'Bernhard'));

        $this->assertFalse($group->hasErrors());
    }

    public function testBindForwardsPreprocessedData()
    {
        $field = $this->createMockField('firstName');

        $group = $this->getMock(
            'Symfony\Tests\Component\Form\Fixtures\TestFieldGroup',
            array('preprocessData'), // only mock preprocessData()
            array('author')
        );

        // The data array is prepared directly after binding
        $group->expects($this->once())
              ->method('preprocessData')
              ->with($this->equalTo(array('firstName' => 'Bernhard')))
              ->will($this->returnValue(array('firstName' => 'preprocessed[Bernhard]')));
        $group->add($field);

        // The preprocessed data is then forwarded to the fields
        $field->expects($this->once())
                    ->method('bind')
                    ->with($this->equalTo('preprocessed[Bernhard]'));

        $group->bind(array('firstName' => 'Bernhard'));
    }

    public function testBindForwardsNullIfValueIsMissing()
    {
        $field = $this->createMockField('firstName');
        $field->expects($this->once())
                    ->method('bind')
                    ->with($this->equalTo(null));

        $group = new TestFieldGroup('author');
        $group->add($field);

        $group->bind(array());
    }

    public function testAddErrorMapsFieldValidationErrorsOntoFields()
    {
        $error = new FieldError('Message');

        $field = $this->createMockField('firstName');
        $field->expects($this->once())
                    ->method('addError')
                    ->with($this->equalTo($error));

        $group = new TestFieldGroup('author');
        $group->add($field);

        $path = new PropertyPath('fields[firstName].data');

        $group->addError($error, $path->getIterator(), FieldGroup::FIELD_ERROR);
    }

    public function testAddErrorMapsFieldValidationErrorsOntoFieldsWithinNestedFieldGroups()
    {
        $error = new FieldError('Message');

        $field = $this->createMockField('firstName');
        $field->expects($this->once())
                    ->method('addError')
                    ->with($this->equalTo($error));

        $group = new TestFieldGroup('author');
        $innerGroup = new TestFieldGroup('names');
        $innerGroup->add($field);
        $group->add($innerGroup);

        $path = new PropertyPath('fields[names].fields[firstName].data');

        $group->addError($error, $path->getIterator(), FieldGroup::FIELD_ERROR);
    }

    public function testAddErrorKeepsFieldValidationErrorsIfFieldNotFound()
    {
        $error = new FieldError('Message');

        $field = $this->createMockField('foo');
        $field->expects($this->never())
                    ->method('addError');

        $group = new TestFieldGroup('author');
        $group->add($field);

        $path = new PropertyPath('fields[bar].data');

        $group->addError($error, $path->getIterator(), FieldGroup::FIELD_ERROR);

        $this->assertEquals(array($error), $group->getErrors());
    }

    public function testAddErrorKeepsFieldValidationErrorsIfFieldIsHidden()
    {
        $error = new FieldError('Message');

        $field = $this->createMockField('firstName');
        $field->expects($this->any())
                    ->method('isHidden')
                    ->will($this->returnValue(true));
        $field->expects($this->never())
                    ->method('addError');

        $group = new TestFieldGroup('author');
        $group->add($field);

        $path = new PropertyPath('fields[firstName].data');

        $group->addError($error, $path->getIterator(), FieldGroup::FIELD_ERROR);

        $this->assertEquals(array($error), $group->getErrors());
    }

    public function testAddErrorMapsDataValidationErrorsOntoFields()
    {
        $error = new FieldError('Message');

        // path is expected to point at "firstName"
        $expectedPath = new PropertyPath('firstName');
        $expectedPathIterator = $expectedPath->getIterator();

        $field = $this->createMockField('firstName');
        $field->expects($this->any())
                    ->method('getPropertyPath')
                    ->will($this->returnValue(new PropertyPath('firstName')));
        $field->expects($this->once())
                    ->method('addError')
                    ->with($this->equalTo($error), $this->equalTo($expectedPathIterator), $this->equalTo(FieldGroup::DATA_ERROR));

        $group = new TestFieldGroup('author');
        $group->add($field);

        $path = new PropertyPath('firstName');

        $group->addError($error, $path->getIterator(), FieldGroup::DATA_ERROR);
    }

    public function testAddErrorKeepsDataValidationErrorsIfFieldNotFound()
    {
        $error = new FieldError('Message');

        $field = $this->createMockField('foo');
        $field->expects($this->any())
                    ->method('getPropertyPath')
                    ->will($this->returnValue(new PropertyPath('foo')));
        $field->expects($this->never())
                    ->method('addError');

        $group = new TestFieldGroup('author');
        $group->add($field);

        $path = new PropertyPath('bar');

        $group->addError($error, $path->getIterator(), FieldGroup::DATA_ERROR);
    }

    public function testAddErrorKeepsDataValidationErrorsIfFieldIsHidden()
    {
        $error = new FieldError('Message');

        $field = $this->createMockField('firstName');
        $field->expects($this->any())
                    ->method('isHidden')
                    ->will($this->returnValue(true));
        $field->expects($this->any())
                    ->method('getPropertyPath')
                    ->will($this->returnValue(new PropertyPath('firstName')));
        $field->expects($this->never())
                    ->method('addError');

        $group = new TestFieldGroup('author');
        $group->add($field);

        $path = new PropertyPath('firstName');

        $group->addError($error, $path->getIterator(), FieldGroup::DATA_ERROR);
    }

    public function testAddErrorMapsDataValidationErrorsOntoNestedFields()
    {
        $error = new FieldError('Message');

        // path is expected to point at "street"
        $expectedPath = new PropertyPath('address.street');
        $expectedPathIterator = $expectedPath->getIterator();
        $expectedPathIterator->next();

        $field = $this->createMockField('address');
        $field->expects($this->any())
                    ->method('getPropertyPath')
                    ->will($this->returnValue(new PropertyPath('address')));
        $field->expects($this->once())
                    ->method('addError')
                    ->with($this->equalTo($error), $this->equalTo($expectedPathIterator), $this->equalTo(FieldGroup::DATA_ERROR));

        $group = new TestFieldGroup('author');
        $group->add($field);

        $path = new PropertyPath('address.street');

        $group->addError($error, $path->getIterator(), FieldGroup::DATA_ERROR);
    }

    public function testAddErrorMapsErrorsOntoFieldsInAnonymousGroups()
    {
        $error = new FieldError('Message');

        // path is expected to point at "address"
        $expectedPath = new PropertyPath('address');
        $expectedPathIterator = $expectedPath->getIterator();

        $field = $this->createMockField('address');
        $field->expects($this->any())
                    ->method('getPropertyPath')
                    ->will($this->returnValue(new PropertyPath('address')));
        $field->expects($this->once())
                    ->method('addError')
                    ->with($this->equalTo($error), $this->equalTo($expectedPathIterator), $this->equalTo(FieldGroup::DATA_ERROR));

        $group = new TestFieldGroup('author');
        $group2 = new TestFieldGroup('anonymous', array('property_path' => null));
        $group2->add($field);
        $group->add($group2);

        $path = new PropertyPath('address');

        $group->addError($error, $path->getIterator(), FieldGroup::DATA_ERROR);
    }

    public function testAddThrowsExceptionIfAlreadyBound()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createMockField('firstName'));
        $group->bind(array('firstName' => 'Bernhard'));

        $this->setExpectedException('Symfony\Component\Form\Exception\AlreadyBoundException');
        $group->add($this->createMockField('lastName'));
    }

    public function testAddSetsFieldParent()
    {
        $group = new TestFieldGroup('author');

        $field = $this->createMockField('firstName');
        $field->expects($this->once())
                    ->method('setParent');
                    // PHPUnit fails to compare infinitely recursive objects
                    //->with($this->equalTo($group));

        $group->add($field);
    }

    public function testRemoveUnsetsFieldParent()
    {
        $group = new TestFieldGroup('author');

        $field = $this->createMockField('firstName');
        $field->expects($this->exactly(2))
                    ->method('setParent');
                    // PHPUnit fails to compare subsequent method calls with different arguments

        $group->add($field);
        $group->remove('firstName');
    }

    public function testMergeAddsFieldsFromAnotherGroup()
    {
        $group1 = new TestFieldGroup('author');
        $group1->add($field1 = new TestField('firstName'));

        $group2 = new TestFieldGroup('publisher');
        $group2->add($field2 = new TestField('lastName'));

        $group1->merge($group2);

        $this->assertTrue($group1->has('lastName'));
        $this->assertEquals(new PropertyPath('publisher.lastName'), $group1->get('lastName')->getPropertyPath());
    }

    public function testMergeThrowsExceptionIfOtherGroupAlreadyBound()
    {
        $group1 = new TestFieldGroup('author');
        $group2 = new TestFieldGroup('publisher');
        $group2->add($this->createMockField('firstName'));

        $group2->bind(array('firstName' => 'Bernhard'));

        $this->setExpectedException('Symfony\Component\Form\Exception\AlreadyBoundException');
        $group1->merge($group2);
    }

    public function testAddUpdatesFieldFromTransformedData()
    {
        $originalAuthor = new Author();
        $transformedAuthor = new Author();
        // the authors should differ to make sure the test works
        $transformedAuthor->firstName = 'Foo';

        $group = new TestFieldGroup('author');

        $transformer = $this->createMockTransformer();
        $transformer->expects($this->once())
                                ->method('transform')
                                ->with($this->equalTo($originalAuthor))
                                ->will($this->returnValue($transformedAuthor));

        $group->setValueTransformer($transformer);
        $group->setData($originalAuthor);

        $field = $this->createMockField('firstName');
        $field->expects($this->any())
                    ->method('getPropertyPath')
                    ->will($this->returnValue(new PropertyPath('firstName')));
        $field->expects($this->once())
                    ->method('updateFromObject')
                    ->with($this->equalTo($transformedAuthor));

        $group->add($field);
    }

    public function testAddDoesNotUpdateFieldsWithEmptyPropertyPath()
    {
        $group = new TestFieldGroup('author');
        $group->setData(new Author());

        $field = $this->createMockField('firstName');
        $field->expects($this->any())
                    ->method('getPropertyPath')
                    ->will($this->returnValue(null));
        $field->expects($this->never())
                    ->method('updateFromObject');

        $group->add($field);
    }

    public function testAddDoesNotUpdateFieldIfTransformedDataIsEmpty()
    {
        $originalAuthor = new Author();

        $group = new TestFieldGroup('author');

        $transformer = $this->createMockTransformer();
        $transformer->expects($this->once())
                                ->method('transform')
                                ->with($this->equalTo($originalAuthor))
                                ->will($this->returnValue(''));

        $group->setValueTransformer($transformer);
        $group->setData($originalAuthor);

        $field = $this->createMockField('firstName');
        $field->expects($this->never())
                    ->method('updateFromObject');

        $group->add($field);
    }

    public function testSetDataUpdatesAllFieldsFromTransformedData()
    {
        $originalAuthor = new Author();
        $transformedAuthor = new Author();
        // the authors should differ to make sure the test works
        $transformedAuthor->firstName = 'Foo';

        $group = new TestFieldGroup('author');

        $transformer = $this->createMockTransformer();
        $transformer->expects($this->once())
                                ->method('transform')
                                ->with($this->equalTo($originalAuthor))
                                ->will($this->returnValue($transformedAuthor));

        $group->setValueTransformer($transformer);

        $field = $this->createMockField('firstName');
        $field->expects($this->once())
                    ->method('updateFromObject')
                    ->with($this->equalTo($transformedAuthor));

        $group->add($field);

        $field = $this->createMockField('lastName');
        $field->expects($this->once())
                    ->method('updateFromObject')
                    ->with($this->equalTo($transformedAuthor));

        $group->add($field);

        $group->setData($originalAuthor);
    }

    public function testSetDataThrowsAnExceptionIfArgumentIsNotObjectOrArray()
    {
        $group = new TestFieldGroup('author');

        $this->setExpectedException('InvalidArgumentException');

        $group->setData('foobar');
    }

    public function testBindUpdatesTransformedDataFromAllFields()
    {
        $originalAuthor = new Author();
        $transformedAuthor = new Author();
        // the authors should differ to make sure the test works
        $transformedAuthor->firstName = 'Foo';

        $group = new TestFieldGroup('author');

        $transformer = $this->createMockTransformer();
        $transformer->expects($this->exactly(2))
                                ->method('transform')
                                // the method is first called with NULL, then
                                // with $originalAuthor -> not testable by PHPUnit
                                // ->with($this->equalTo(null))
                                // ->with($this->equalTo($originalAuthor))
                                ->will($this->returnValue($transformedAuthor));

        $group->setValueTransformer($transformer);
        $group->setData($originalAuthor);

        $field = $this->createMockField('firstName');
        $field->expects($this->once())
                    ->method('updateObject')
                    ->with($this->equalTo($transformedAuthor));

        $group->add($field);

        $field = $this->createMockField('lastName');
        $field->expects($this->once())
                    ->method('updateObject')
                    ->with($this->equalTo($transformedAuthor));

        $group->add($field);

        $group->bind(array()); // irrelevant
    }

    public function testGetDataReturnsObject()
    {
        $group = new TestFieldGroup('author');
        $object = new \stdClass();
        $group->setData($object);
        $this->assertEquals($object, $group->getData());
    }

    public function testGetDisplayedDataForwardsCall()
    {
        $field = $this->createValidMockField('firstName');
        $field->expects($this->atLeastOnce())
                    ->method('getDisplayedData')
                    ->will($this->returnValue('Bernhard'));

        $group = new TestFieldGroup('author');
        $group->add($field);

        $this->assertEquals(array('firstName' => 'Bernhard'), $group->getDisplayedData());
    }

    public function testIsMultipartIfAnyFieldIsMultipart()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createMultipartMockField('firstName'));
        $group->add($this->createNonMultipartMockField('lastName'));

        $this->assertTrue($group->isMultipart());
    }

    public function testIsNotMultipartIfNoFieldIsMultipart()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createNonMultipartMockField('firstName'));
        $group->add($this->createNonMultipartMockField('lastName'));

        $this->assertFalse($group->isMultipart());
    }

    public function testLocaleIsPassedToField_SetBeforeAddingTheField()
    {
        $field = $this->getMock('Symfony\Component\Form\Field', array(), array(), '', false, false);
        $field->expects($this->any())
                    ->method('getKey')
                    ->will($this->returnValue('firstName'));
        $field->expects($this->once())
                    ->method('setLocale')
                    ->with($this->equalTo('de_DE'));

        $group = new TestFieldGroup('author');
        $group->setLocale('de_DE');
        $group->add($field);
    }

    public function testLocaleIsPassedToField_SetAfterAddingTheField()
    {
        $field = $this->getMockForAbstractClass(__NAMESPACE__ . '\FieldGroupTest_Field', array(), '', false, false);
        $field->expects($this->any())
                    ->method('getKey')
                    ->will($this->returnValue('firstName'));
// DOESN'T WORK!
//    $field = $this->getMock(__NAMESPACE__ . '\Fixtures\Field', array(), array(), '', false, false);
//    $field->expects($this->once())
//          ->method('setLocale')
//          ->with($this->equalTo('de_AT'));
//    $field->expects($this->once())
//          ->method('setLocale')
//          ->with($this->equalTo('de_DE'));

        $group = new TestFieldGroup('author');
        $group->add($field);
        $group->setLocale('de_DE');

        $this->assertEquals(array(class_exists('\Locale', false) ? \Locale::getDefault() : 'en', 'de_DE'), $field->locales);
    }

    public function testSupportsClone()
    {
        $group = new TestFieldGroup('author');
        $group->add($this->createMockField('firstName'));

        $clone = clone $group;

        $this->assertNotSame($clone['firstName'], $group['firstName']);
    }

    public function testBindWithoutPriorSetData()
    {
        return; // TODO
        $field = $this->createMockField('firstName');
        $field->expects($this->any())
                    ->method('getData')
                    ->will($this->returnValue('Bernhard'));

        $group = new TestFieldGroup('author');
        $group->add($field);

        $group->bind(array('firstName' => 'Bernhard'));

        $this->assertEquals(array('firstName' => 'Bernhard'), $group->getData());
    }

    public function testGetHiddenFieldsReturnsOnlyHiddenFields()
    {
        $group = $this->getGroupWithBothVisibleAndHiddenField();

        $hiddenFields = $group->getHiddenFields(true, false);

        $this->assertSame(array($group['hiddenField']), $hiddenFields);
    }

    public function testGetVisibleFieldsReturnsOnlyVisibleFields()
    {
        $group = $this->getGroupWithBothVisibleAndHiddenField();

        $visibleFields = $group->getVisibleFields(true, false);

        $this->assertSame(array($group['visibleField']), $visibleFields);
    }
    
    public function testSubCollectionsModify()
    {
        $dataToSet = array('entry_1', 'entry_2', 'entry_3', 'entry_4');
        $collectionDataToBind = array('book_1', 'book_2');
        
        $group = new TestFieldGroup('author');
        $group->add(new TestField('firstName'));
        $group->add($this->createCollectionFieldModifiableTrue('books', $dataToSet));
        $group->bind(array('firstName' => 'Bernhard', 'books' => $collectionDataToBind));
        
        $this->assertEquals($group->get('firstName'), $group['firstName']);
        $this->assertEquals('Bernhard', $group->get('firstName')->getData());
        $this->assertEquals('Bernhard', $group['firstName']->getData());
        
        $this->assertEquals($group->get('books'), $group['books']);
        
        $this->assertEquals(count($collectionDataToBind), count($group->get('books')->getData())); //FAIL
        $this->assertEquals(count($collectionDataToBind), count($group['books']->getData())); //FAIL
        
        $this->assertEquals($collectionDataToBind, $group->get('books')->getData()); //FAIL
        $this->assertEquals($collectionDataToBind, $group['books']->getData()); //FAIL
    }

    /**
     * Create a group containing two fields, "visibleField" and "hiddenField"
     *
     * @return FieldGroup
     */
    protected function getGroupWithBothVisibleAndHiddenField()
    {
        $group = new TestFieldGroup('testGroup');

        // add a visible field
        $visibleField = $this->createMockField('visibleField');
        $visibleField->expects($this->once())
                    ->method('isHidden')
                    ->will($this->returnValue(false));
        $group->add($visibleField);

        // add a hidden field
        $hiddenField = $this->createMockField('hiddenField');
        $hiddenField->expects($this->once())
                    ->method('isHidden')
                    ->will($this->returnValue(true));
        $group->add($hiddenField);

        return $group;
    }

    protected function createMockField($key)
    {
        $field = $this->getMock(
            'Symfony\Component\Form\FieldInterface',
            array(),
            array(),
            '',
            false, // don't use constructor
            false  // don't call parent::__clone
        );

        $field->expects($this->any())
                    ->method('getKey')
                    ->will($this->returnValue($key));

        return $field;
    }

    protected function createInvalidMockField($key)
    {
        $field = $this->createMockField($key);
        $field->expects($this->any())
                    ->method('isValid')
                    ->will($this->returnValue(false));

        return $field;
    }

    protected function createValidMockField($key)
    {
        $field = $this->createMockField($key);
        $field->expects($this->any())
                    ->method('isValid')
                    ->will($this->returnValue(true));

        return $field;
    }

    protected function createNonMultipartMockField($key)
    {
        $field = $this->createMockField($key);
        $field->expects($this->any())
                    ->method('isMultipart')
                    ->will($this->returnValue(false));

        return $field;
    }

    protected function createMultipartMockField($key)
    {
        $field = $this->createMockField($key);
        $field->expects($this->any())
                    ->method('isMultipart')
                    ->will($this->returnValue(true));

        return $field;
    }

    protected function createMockTransformer()
    {
        return $this->getMock('Symfony\Component\Form\ValueTransformer\ValueTransformerInterface', array(), array(), '', false, false);
    }
    
    protected function createCollectionFieldModifiableTrue($key, $dataToSet)
    {
        $field = new CollectionField(new TestField($key), array('modifiable' => true));
        $field->setData($dataToSet);
        $field->remove('$$key$$');
        
        return $field;
    }
}
