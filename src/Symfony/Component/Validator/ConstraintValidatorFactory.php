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

use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Constraint;

class ConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    protected $validators = array();

    public function getInstance(Constraint $constraint)
    {
        $className = $constraint->validatedBy();

        if (!isset($this->validators[$className])) {
            $this->validators[$className] = new $className();
        }

        return $this->validators[$className];
    }
}