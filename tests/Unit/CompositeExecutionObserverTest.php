<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\CompositeExecutionObserver;
use Maduser\Argon\Workflows\ExecutionEvent;
use Maduser\Argon\Workflows\Tests\Unit\Fixtures\CollectingObserver;
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
