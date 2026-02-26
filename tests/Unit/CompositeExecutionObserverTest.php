<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\CompositeExecutionObserver;
use Maduser\Argon\Workflows\Contracts\ExecutionObserverInterface;
use Maduser\Argon\Workflows\ExecutionEvent;
use PHPUnit\Framework\TestCase;

final class CompositeExecutionObserverTest extends TestCase
{
    public function testForwardsEventsToAllObservers(): void
    {
        $first = new CollectingObserver();
        $second = new CollectingObserver();
        $composite = new CompositeExecutionObserver($first, $second);
        $event = new ExecutionEvent(
            ExecutionEvent::TYPE_RUN_STARTED,
            'run-1',
            'default',
            'start',
            123,
            ['meta' => 'value']
        );

        $composite->emit($event);

        $this->assertSame([$event], $first->events);
        $this->assertSame([$event], $second->events);
    }
}

final class CollectingObserver implements ExecutionObserverInterface
{
    /** @var list<ExecutionEvent> */
    public array $events = [];

    public function emit(ExecutionEvent $event): void
    {
        $this->events[] = $event;
    }
}
