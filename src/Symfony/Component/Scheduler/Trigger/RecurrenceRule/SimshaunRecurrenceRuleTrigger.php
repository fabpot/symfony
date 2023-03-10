<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Scheduler\Trigger\RecurrenceRule;

use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;
use Recurr\Transformer\Constraint\AfterConstraint;
use Symfony\Component\Scheduler\Exception\LogicException;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @experimental
 */
final class SimshaunRecurrenceRuleTrigger implements RecurrenceRuleInterface
{
    private ArrayTransformer $transformer;

    public function __construct(
        private Rule $rule,
    ) {
        $cfg = new ArrayTransformerConfig();
        $cfg->setVirtualLimit(1);

        $this->transformer = new ArrayTransformer($cfg);
    }

    public static function fromSpec(string $rule, ?\DateTimeImmutable $from = null): self
    {
        if (!class_exists(Rule::class)) {
            throw new LogicException(sprintf('You cannot use "%s" as the "simshaun/recurr" package is not installed; try running "composer require simshaun/recurr".', __CLASS__));
        }

        return new self(new Rule($rule, $from));
    }

    public function getNextRunDate(\DateTimeImmutable $run): ?\DateTimeImmutable
    {
        if (!$recurrences = $this->transformer->transform($this->rule, new AfterConstraint($run))) {
            return null;
        }

        return \DateTimeImmutable::createFromMutable($recurrences[0]->getStart());
    }
}
