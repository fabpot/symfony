<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates a given value.
 *
 * @author Bernhard Schussek <bernhard.schussek@symfony-project.com>
 */
interface ValidatorInterface
{
    function validate($object, $groups = null);

    function validateProperty($object, $property, $groups = null);

    function validatePropertyValue($class, $property, $value, $groups = null);

    function validateValue($value, Constraint $constraint, $groups = null);
}