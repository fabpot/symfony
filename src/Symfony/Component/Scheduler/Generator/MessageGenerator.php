<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Scheduler\Generator;

use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleableInterface;
use Symfony\Component\Scheduler\Trigger\TriggerInterface;

/**
 * @experimental
 */
final class MessageGenerator implements MessageGeneratorInterface
{
    private TriggerHeap $triggerHeap;
    private ?\DateTimeImmutable $waitUntil;
    private CheckpointInterface $checkpoint;
    private Schedule $schedule;

    public function __construct(
        private readonly string $name,
        ScheduleableInterface $scheduleProvider,
        private readonly ClockInterface $clock = new Clock(),
    ) {
        $this->waitUntil = new \DateTimeImmutable('@0');
        $this->schedule = $scheduleProvider->getSchedule();
        $this->checkpoint = new Checkpoint('scheduler_checkpoint_'.$name, $this->schedule->getLock(), $this->schedule->getState());
    }

    public function withCheckpoint(CheckpointInterface $checkpoint): static
    {
        $generator = clone $this;
        $generator->checkpoint = $checkpoint;

        return $generator;
    }

    public function getMessages(): \Generator
    {
        if (!$this->waitUntil
            || $this->waitUntil > ($now = $this->clock->now())
            || !$this->checkpoint->acquire($now)
        ) {
            return;
        }

        $lastTime = $this->checkpoint->time();
        $lastIndex = $this->checkpoint->index();
        $heap = $this->heap($lastTime);

        while (!$heap->isEmpty() && $heap->top()[0] <= $now) {
            /** @var TriggerInterface $trigger */
            [$time, $index, $trigger, $message] = $heap->extract();
            $yield = true;

            if ($time < $lastTime) {
                $time = $lastTime;
                $yield = false;
            } elseif ($time == $lastTime && $index <= $lastIndex) {
                $yield = false;
            }

            if ($nextTime = $trigger->getNextRunDate($time)) {
                $heap->insert([$nextTime, $index, $trigger, $message]);
            }

            if ($yield) {
                yield $message;
                $this->checkpoint->save($time, $index);
            }
        }

        $this->waitUntil = $heap->isEmpty() ? null : $heap->top()[0];

        $this->checkpoint->release($now, $this->waitUntil);
    }

    private function heap(\DateTimeImmutable $time): TriggerHeap
    {
        if (isset($this->triggerHeap) && $this->triggerHeap->time <= $time) {
            return $this->triggerHeap;
        }

        $heap = new TriggerHeap($time);

        foreach ($this->schedule->getRecurringMessages() as $index => $recurringMessage) {
            if (!$nextTime = $recurringMessage->getTrigger()->getNextRunDate($time)) {
                continue;
            }

            $heap->insert([$nextTime, $index, $recurringMessage->getTrigger(), $recurringMessage->getMessage()]);
        }

        return $this->triggerHeap = $heap;
    }
}
