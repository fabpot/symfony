<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Scheduler\Tests\Messenger;

use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Scheduler\Exception\InvalidArgumentException;
use Symfony\Component\Scheduler\Generator\MessageGenerator;
use Symfony\Component\Scheduler\Messenger\SchedulerTransport;
use Symfony\Component\Scheduler\Messenger\SchedulerTransportFactory;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleableInterface;
use Symfony\Component\Scheduler\Trigger\TriggerInterface;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class SchedulerTransportFactoryTest extends TestCase
{
    public function testCreateTransport()
    {
        $trigger = $this->createMock(TriggerInterface::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $clock = $this->createMock(ClockInterface::class);

        $defaultRecurringMessage = new RecurringMessage((object) ['id' => 'default'], $trigger);
        $customRecurringMessage = new RecurringMessage((object) ['id' => 'custom'], $trigger);

        $default = new SchedulerTransport(new MessageGenerator('default', new SomeSchedule([$defaultRecurringMessage]), $clock));
        $custom = new SchedulerTransport(new MessageGenerator('custom', new SomeSchedule([$customRecurringMessage]), $clock));

        $factory = new SchedulerTransportFactory(
            new Container([
                'default' => fn () => new SomeSchedule([$defaultRecurringMessage]),
                'custom' => fn () => new SomeSchedule([$customRecurringMessage]),
            ]),
            $clock,
        );

        $this->assertEquals($default, $factory->createTransport('schedule://default', [], $serializer));
        $this->assertEquals($custom, $factory->createTransport('schedule://custom', ['cache' => 'app'], $serializer));
    }

    public function testInvalidDsn()
    {
        $factory = $this->makeTransportFactoryWithStubs();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given Schedule DSN "schedule://#wrong" is invalid.');

        $factory->createTransport('schedule://#wrong', [], $this->createMock(SerializerInterface::class));
    }

    public function testNoName()
    {
        $factory = $this->makeTransportFactoryWithStubs();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Schedule DSN must contains a name, e.g. "schedule://default".');

        $factory->createTransport('schedule://', [], $this->createMock(SerializerInterface::class));
    }

    public function testNotFound()
    {
        $factory = $this->makeTransportFactoryWithStubs();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The schedule "not-exists" is not found.');

        $factory->createTransport('schedule://not-exists', [], $this->createMock(SerializerInterface::class));
    }

    public function testSupports()
    {
        $factory = $this->makeTransportFactoryWithStubs();

        $this->assertTrue($factory->supports('schedule://', []));
        $this->assertTrue($factory->supports('schedule://name', []));
        $this->assertFalse($factory->supports('', []));
        $this->assertFalse($factory->supports('string', []));
    }

    private function makeTransportFactoryWithStubs(): SchedulerTransportFactory
    {
        return new SchedulerTransportFactory(
            new Container([
                'default' => fn () => $this->createMock(ScheduleableInterface::class),
            ]),
            $this->createMock(ClockInterface::class),
        );
    }
}

class SomeSchedule implements ScheduleableInterface
{
    public function __construct(
        private array $messages,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(...$this->messages);
    }
}

class Container implements ContainerInterface
{
    use ServiceLocatorTrait;
}
