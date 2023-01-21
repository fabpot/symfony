<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Scheduler;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Scheduler\Exception\LogicException;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;
use Symfony\Component\Scheduler\Trigger\PeriodicalTrigger;
use Symfony\Component\Scheduler\Trigger\TriggerInterface;

/**
 * @experimental
 */
final class RecurringMessage
{
    public function __construct(
        private object $message,
        private TriggerInterface $trigger = new CronExpressionTrigger(),
    ) {
    }

    public function getMessage(): object
    {
        return $this->message;
    }

    public function getTrigger(): TriggerInterface
    {
        return $this->trigger;
    }

    /**
     * @return $this
     */
    public function transport(string $name, string ...$names): self
    {
        $this->message = Envelope::wrap($this->message, [new TransportNamesStamp([$name, ...$names])]);

        return $this;
    }

    /**
     * @return $this
     */
    public function trigger(TriggerInterface $trigger): self
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * @return $this
     */
    public function every(string $expression): self
    {
        $this->trigger = PeriodicalTrigger::create(
            \DateInterval::createFromDateString($expression),
            'now',
        );

        return $this;
    }
}
