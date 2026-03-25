<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Loop;

use Revolt\EventLoop;

/**
 * Drives the main TUI tick interval using adaptive scheduling.
 *
 * @experimental
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class AdaptativeTicker
{
    private const float MIN_INTERVAL = 0.001;

    private ?string $callbackId = null;
    private ?float $interval = null;

    public function __construct(
        private readonly TickRuntimeInterface $runtime,
        private readonly float $activeTickInterval = 0.01,
        private readonly float $idleTickInterval = 0.25,
    ) {
    }

    public function refresh(bool $running, bool $renderRequested, ?float $nextScheduledDelay, bool $hasTickCallback, ?bool $lastTickBusyHint): void
    {
        $this->setInterval($this->computeDesiredInterval($running, $renderRequested, $nextScheduledDelay, $hasTickCallback, $lastTickBusyHint));
    }

    public function stop(): void
    {
        $this->setInterval(null);
    }

    private function computeDesiredInterval(bool $running, bool $renderRequested, ?float $nextScheduledDelay, bool $hasTickCallback, ?bool $lastTickBusyHint): ?float
    {
        if (!$running) {
            return null;
        }

        $intervals = [];

        if ($renderRequested) {
            $intervals[] = $this->activeTickInterval;
        }

        if (null !== $nextScheduledDelay) {
            $intervals[] = $nextScheduledDelay;
        }

        if ($hasTickCallback) {
            if (true === $lastTickBusyHint) {
                $intervals[] = $this->activeTickInterval;
            } elseif (null === $lastTickBusyHint) {
                $intervals[] = $this->idleTickInterval;
            }
        }

        if ([] === $intervals) {
            return null;
        }

        return max(self::MIN_INTERVAL, min($intervals));
    }

    private function setInterval(?float $interval): void
    {
        if (null === $interval) {
            if (null !== $this->callbackId) {
                EventLoop::cancel($this->callbackId);
                $this->callbackId = null;
            }
            $this->interval = null;

            return;
        }

        if (null !== $this->interval && abs($this->interval - $interval) < 0.0001) {
            return;
        }

        if (null !== $this->callbackId) {
            EventLoop::cancel($this->callbackId);
            $this->callbackId = null;
        }

        $this->interval = $interval;
        $this->callbackId = EventLoop::repeat($interval, function (string $callbackId): void {
            if (!$this->runtime->isRunning()) {
                EventLoop::cancel($callbackId);

                if ($this->callbackId === $callbackId) {
                    $this->callbackId = null;
                    $this->interval = null;
                }

                return;
            }

            $this->runtime->tick();
        });
    }
}
