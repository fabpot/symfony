<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Scheduler\Trigger;

use RRule\RRule;
use Symfony\Component\Scheduler\Exception\LogicException;
use Symfony\Component\Scheduler\Trigger\RecurrenceRule\RecurrenceRuleInterface;
use Symfony\Component\Scheduler\Trigger\RecurrenceRule\RlanvinRecurrenceRuleTrigger;
use Symfony\Component\Scheduler\Trigger\RecurrenceRule\SimshaunRecurrenceRuleTrigger;

/**
 * Use RFC 5545 strings to describe a periodical trigger.
 *
 * @see https://www.rfc-editor.org/rfc/rfc5545#section-3.3.10
 * @see https://www.rfc-editor.org/rfc/rfc5545#section-3.8.5.3
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @experimental
 */
final class RecurrenceRuleTrigger implements TriggerInterface
{
    public function __construct(
        private readonly RecurrenceRuleInterface $rule,
    ) {
    }

    public static function fromSpec(string $rule, ?\DateTimeImmutable $from = null): self
    {
        if (class_exists(RRule::class)) {
            $class = RlanvinRecurrenceRuleTrigger::class;
        } elseif (class_exists(RRule::class)) {
            $class = SimshaunRecurrenceRuleTrigger::class;
        } else {
            throw new LogicException(sprintf('You cannot use "%s" as the no package support RRULE is installed; try running "composer require rlanvin/php-rrule".', __CLASS__));
        }

        return new self($class::fromSpec($rule, $from));
    }

    public function getNextRunDate(\DateTimeImmutable $run): ?\DateTimeImmutable
    {
        return $this->rule->getNextRunDate($run);
    }
}
