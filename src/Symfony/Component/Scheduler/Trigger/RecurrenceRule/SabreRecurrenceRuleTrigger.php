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

use Sabre\VObject\Recur\RRuleIterator;
use Symfony\Component\Scheduler\Exception\LogicException;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @experimental
 */
final class SabreRecurrenceRuleTrigger implements RecurrenceRuleInterface
{
    public function __construct(
        private RRuleIterator $rule,
    ) {
    }

    public static function fromSpec(string $rule, ?\DateTimeImmutable $from = null): self
    {
        if (!class_exists(RRuleIterator::class)) {
            throw new LogicException(sprintf('You cannot use "%s" as the "sabre/vobject" package is not installed; try running "composer require sabre/vobject".', __CLASS__));
        }

        if (!$from) {
            throw new LogicException(sprintf('The "sabre/vobject" package does not support "null" start dates.'));
        }

        return new self(new RRuleIterator($rule, $from));
    }

    public function getNextRunDate(\DateTimeImmutable $run): ?\DateTimeImmutable
    {
        $this->rule->fastForward($run->modify('+1 second'));

        return $this->rule->valid() ? $this->rule->current() : null;
    }
}
