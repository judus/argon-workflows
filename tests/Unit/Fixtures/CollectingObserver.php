<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit\Fixtures;

use Maduser\Argon\Workflows\Contracts\ExecutionObserverInterface;
use Maduser\Argon\Workflows\ExecutionEvent;

final class CollectingObserver implements ExecutionObserverInterface
{
    /** @var list<ExecutionEvent> */
    public array $events = [];

    #[\Override]
    public function emit(ExecutionEvent $event): void
    {
        $this->events[] = $event;
    }
}
