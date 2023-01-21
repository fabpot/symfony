<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Scheduler\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Scheduler\Exception\LogicException;
use Symfony\Component\Scheduler\Generator\MessageGeneratorInterface;

/**
 * @experimental
 */
class SchedulerTransport implements TransportInterface, MessageCountAwareInterface
{
    public function __construct(
        private readonly MessageGeneratorInterface $messageGenerator,
    ) {
    }

    public function getMessageCount(): int
    {
        return 0;
    }

    public function get(): iterable
    {
        foreach ($this->messageGenerator->getMessages() as $message) {
            yield new Envelope(new RedispatchMessage(Envelope::wrap($message, [new ScheduledStamp()])));
        }
    }

    public function ack(Envelope $envelope): void
    {
        // ignore
    }

    public function reject(Envelope $envelope): void
    {
        throw new LogicException(sprintf('Messages from "%s" must not be rejected.', __CLASS__));
    }

    public function send(Envelope $envelope): Envelope
    {
        throw new LogicException(sprintf('"%s" cannot send messages.', __CLASS__));
    }
}
