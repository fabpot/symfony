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

use RRule\RRule;
use RRule\RRuleInterface;
use Symfony\Component\Scheduler\Exception\LogicException;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @experimental
 */
final class RlanvinRecurrenceRuleTrigger implements RecurrenceRuleInterface
{
    public function __construct(
        private RRuleInterface $rule,
    ) {
    }

    public static function fromSpec(string $rule, ?\DateTimeImmutable $from = null): self
    {
        if (!class_exists(RRule::class)) {
            throw new LogicException(sprintf('You cannot use "%s" as the "rlanvin/php-rrule" package is not installed; try running "composer require rlanvin/php-rrule".', __CLASS__));
        }

        return new self(new RRule($rule, $from));
    }

    public function getNextRunDate(\DateTimeImmutable $run): ?\DateTimeImmutable
    {
        if (!$dates = $this->rule->getOccurrencesAfter($run, false, 1)) {
            return null;
        }

        return \DateTimeImmutable::createFromMutable($dates[0]);
    }
}
