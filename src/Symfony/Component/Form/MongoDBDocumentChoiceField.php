<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form;

use Symfony\Component\Form\ValueTransformer\TransformationFailedException;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\Exception\InvalidOptionsException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * A field for selecting one or more from a list of Doctrine 2 MongoDB ODM documents
 *
 * You at least have to pass the document manager and the document class in the
 * options "dm" and "class".
 *
 * <code>
 * $form->add(new MongoDBDocumentChoiceField('tags', array(
 *     'dm' => $dm,
 *     'class' => 'Application\Document\Tag',
 * )));
 * </code>
 *
 * Additionally to the options in ChoiceField, the following options are
 * available:
 *
 *  * dm:             The document manager. Required.
 *  * class:          The class of the selectable documents. Required.
 *  * property:       The property displayed as value of the choices. If this
 *                    option is not available, the field will try to convert
 *                    objects into strings using __toString().
 *  * query_builder:  The query builder for fetching the selectable documents.
 *                    You can also pass a closure that receives the repository
 *                    as single argument and returns a query builder.
 *
 * The following sample outlines the use of the "query_builder" option
 * with closures.
 *
 * <code>
 * $form->add(new MongoDBDocumentChoiceField('tags', array(
 *     'dm' => $dm,
 *     'class' => 'Application\Document\Tag',
 *     'query_builder' => function ($repository) {
 *         return $repository->createQueryBuilder()->field('enabled')->equals(1);
 *     },
 * )));
 * </code>
 *
 * @author Bernhard Schussek <bernhard.schussek@symfony-project.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class MongoDBDocumentChoiceField extends ChoiceField
{
    /**
     * The documents from which the user can choose
     *
     * This array is either indexed by ID or by key in the choices array
     * (if the ID consists of multiple fields)
     *
     * This property is initialized by initializeChoices(). It should only
     * be accessed through getDocument() and getDocuments().
     *
     * @var Collection
     */
    protected $documents = null;

    /**
     * Contains the query builder that builds the query for fetching the
     * documents
     *
     * This property should only be accessed through getQueryBuilder().
     *
     * @var Doctrine\ODM\MongoDB\Query\Builder
     */
    protected $queryBuilder = null;

    /**
     * A cache for \ReflectionProperty instances for the underlying class
     *
     * This property should only be accessed through getReflProperty().
     *
     * @var array
     */
    protected $reflProperties = array();

    /**
     * A cache for the UnitOfWork instance of Doctrine
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    protected $unitOfWork = null;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addRequiredOption('dm');
        $this->addRequiredOption('class');
        $this->addOption('property');
        $this->addOption('query_builder');

        // Override option - it is not required for this subclass
        $this->addOption('choices', array());

        parent::configure();

        // The documents can be passed directly in the "choices" option.
        // In this case, initializing the document cache is a cheap operation
        // so do it now!
        if (is_array($this->getOption('choices')) && count($this->getOption('choices')) > 0) {
            $this->initializeChoices();
        }

        // If a query builder was passed, it must be a closure or QueryBuilder
        // instance
        if ($qb = $this->getOption('query_builder')) {
            if (!($qb instanceof Builder || $qb instanceof \Closure)) {
                throw new InvalidOptionsException(
                    'The option "query_builder" most contain a closure or a Query\Builder instance',
                    array('query_builder'));
            }
        }
    }

    /**
     * Returns the query builder instance for the choices of this field
     *
     * @return Doctrine\ODM\MongoDB\Query\Builder  The query builder
     * @throws InvalidOptionsException    When the query builder was passed as
     *                                    closure and that closure does not
     *                                    return a QueryBuilder instance
     */
    protected function getQueryBuilder()
    {
        if (!$this->getOption('query_builder')) {
            return null;
        }

        if (!$this->queryBuilder) {
            $qb = $this->getOption('query_builder');

            if ($qb instanceof \Closure) {
                $class = $this->getOption('class');
                $dm = $this->getOption('dm');
                $qb = $qb($dm->getRepository($class));

                if (!$qb instanceof Builder) {
                    throw new InvalidOptionsException(
                        'The closure in the option "query_builder" should return a Query\Builder instance',
                        array('query_builder'));
                }
            }

            $this->queryBuilder = $qb;
        }

        return $this->queryBuilder;
    }

    /**
     * Returns the unit of work of the document manager
     *
     * This object is cached for faster lookups.
     *
     * @return Doctrine\ODM\MongoDB\UnitOfWork  The unit of work
     */
    protected function getUnitOfWork()
    {
        if (!$this->unitOfWork) {
            $this->unitOfWork = $this->getOption('dm')->getUnitOfWork();
        }

        return $this->unitOfWork;
    }

    /**
     * Initializes the choices and returns them
     *
     * The choices are generated from the documents.
     *
     * If the documents were passed in the "choices" option, this method
     * does not have any significant overhead. Otherwise, if a query builder
     * was passed in the "query_builder" option, this builder is now used
     * to construct a query which is executed. In the last case, all documents
     * for the underlying class are fetched from the repository.
     *
     * If the option "property" was passed, the property path in that option
     * is used as option values. Otherwise this method tries to convert
     * objects to strings using __toString().
     *
     * @return array  An array of choices
     */
    protected function getInitializedChoices()
    {
        if ($this->getOption('choices')) {
            $documents = parent::getInitializedChoices();
        } else if ($qb = $this->getQueryBuilder()) {
            $documents = $qb->getQuery()->execute();
        } else {
            $class = $this->getOption('class');
            $dm = $this->getOption('dm');
            $documents = $dm->getRepository($class)->findAll();
        }

        $propertyPath = null;
        $choices = array();
        $this->documents = array();

        // The propery option defines, which property (path) is used for
        // displaying documents as strings
        if ($this->getOption('property')) {
            $propertyPath = new PropertyPath($this->getOption('property'));
        }

        foreach ($documents as $key => $document) {
            if ($propertyPath) {
                // If the property option was given, use it
                $value = $propertyPath->getValue($document);
            } else {
                // Otherwise expect a __toString() method in the document
                $value = (string)$document;
            }

            // When the identifier is a single field, index choices by
            // document ID for performance reasons
            $id = $this->getIdentifierValue($document);
            $choices[$id] = $value;
            $this->documents[$id] = $document;
        }

        return $choices;
    }

    /**
     * Returns the according documents for the choices
     *
     * If the choices were not initialized, they are initialized now. This
     * is an expensive operation, except if the documents were passed in the
     * "choices" option.
     *
     * @return array  An array of documents
     */
    protected function getDocuments()
    {
        if (!$this->documents) {
            // indirectly initializes the documents property
            $this->initializeChoices();
        }

        return $this->documents;
    }

    /**
     * Returns the document for the given key
     *
     * If the underlying documents have composite identifiers, the choices
     * are intialized. The key is expected to be the index in the choices
     * array in this case.
     *
     * If they have single identifiers, they are either fetched from the
     * internal document cache (if filled) or loaded from the database.
     *
     * @param  string $id  The document ID
     * @return object       The matching document
     */
    protected function getDocument($id)
    {
        return $this->getOption('dm')->find($this->getOption('class'), $id);
    }

    /**
     * Returns the \ReflectionProperty instance for a property of the
     * underlying class
     *
     * @param  string $property     The name of the property
     * @return \ReflectionProperty  The reflection instsance
     */
    protected function getReflProperty($property)
    {
        if (!isset($this->reflProperties[$property])) {
            $this->reflProperties[$property] = new \ReflectionProperty($this->getOption('class'), $property);
            $this->reflProperties[$property]->setAccessible(true);
        }

        return $this->reflProperties[$property];
    }

    /**
     * Returns the values of the identifier fields of an document
     *
     * Doctrine must know about this document, that is, the document must already
     * be persisted or added to the iddocument map before. Otherwise an
     * exception is thrown.
     *
     * @param  object $document  The document for which to get the identifier
     * @throws FormException   If the document does not exist in Doctrine's
     *                         iddocument map
     */
    protected function getIdentifierValue($document)
    {
        if (!$this->getUnitOfWork()->isInIdentityMap($document)) {
            throw new FormException('Documents passed to the choice field must be managed');
        }

        return $this->getUnitOfWork()->getDocumentIdentifier($document);
    }

    /**
     * Merges the selected and deselected documents into the collection passed
     * when calling setData()
     *
     * @see parent::processData()
     */
    protected function processData($data)
    {
        // reuse the existing collection to optimize for Doctrine
        if ($data instanceof Collection) {
            $currentData = $this->getData();

            if (!$currentData) {
                $currentData = $data;
            } else if (count($data) === 0) {
                $currentData->clear();
            } else {
                // merge $data into $currentData
                foreach ($currentData as $document) {
                    if (!$data->contains($document)) {
                        $currentData->removeElement($document);
                    } else {
                        $data->removeElement($document);
                    }
                }

                foreach ($data as $document) {
                    $currentData->add($document);
                }
            }

            return $currentData;
        }

        return $data;
    }

    /**
     * Transforms choice keys into documents
     *
     * @param  mixed $keyOrKeys   An array of keys, a single key or NULL
     * @return Collection|object  A collection of documents, a single document
     *                            or NULL
     */
    protected function reverseTransform($keyOrKeys)
    {
        $keyOrKeys = parent::reverseTransform($keyOrKeys);

        if (null === $keyOrKeys) {
            return $this->getOption('multiple') ? new ArrayCollection() : null;
        }

        $notFound = array_diff((array) $keyOrKeys, array_keys($this->getDocuments()));

        if (0 === count($notFound)) {
            if (is_array($keyOrKeys)) {
                $result = new ArrayCollection();

                // optimize this into a SELECT WHERE IN query
                foreach ($keyOrKeys as $key) {
                    try {
                        $result->add($this->getDocument($key));
                    } catch (NoResultException $e) {
                        $notFound[] = $key;
                    }
                }
            } else {
                try {
                    $result = $this->getDocument($keyOrKeys);
                } catch (NoResultException $e) {
                    $notFound[] = $keyOrKeys;
                }
            }
        }

        if (count($notFound) > 0) {
            throw new TransformationFailedException(sprintf('The documents with keys "%s" could not be found', implode('", "', $notFound)));
        }

        return $result;
    }

    /**
     * Transforms documents into choice keys
     *
     * @param  Collection|object  A collection of documents, a single document or
     *                            NULL
     * @return mixed              An array of choice keys, a single key or
     *                            NULL
     */
    protected function transform($collectionOrDocument)
    {
        if (null === $collectionOrDocument) {
            return $this->getOption('multiple') ? array() : '';
        }

        if ($collectionOrDocument instanceof Collection) {
            $result = array();

            foreach ($collectionOrDocument as $document) {
                $result[] = $this->getIdentifierValue($document);
            }
        } else {
            $result = $this->getIdentifierValue($collectionOrDocument);
        }

        return parent::transform($result);
    }
}