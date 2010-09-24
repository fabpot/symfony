<?php

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MaxValidator extends ConstraintValidator
{
    public function isValid($value, Constraint $constraint)
    {
        if ($value === null) {
            return true;
        }

        if (!is_numeric($value) && !$value instanceof \DateTime) {
            throw new UnexpectedTypeException($value, 'numeric');
        }

        if ($value > $constraint->limit) {
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
                $constraint->limit = $constraint->limit->format('Y-m-d H:i:s');
            }
	
            $this->setMessage($constraint->message, array(
                'value' => $value,
                'limit' => $constraint->limit,
            ));

            return false;
        }

        return true;
    }
}