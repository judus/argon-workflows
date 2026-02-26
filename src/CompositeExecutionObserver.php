<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\ExecutionObserverInterface;

/**
 * Class CompositeExecutionObserver
 * Fan-out observer that forwards events to multiple observers.
 */
final readonly class CompositeExecutionObserver implements ExecutionObserverInterface
{
    /** @var list<ExecutionObserverInterface> */
    private array $observers;

    public function __construct(ExecutionObserverInterface ...$observers)
    {
        $this->observers = array_values($observers);
    }

    #[\Override]
    public function emit(ExecutionEvent $event): void
    {
        foreach ($this->observers as $observer) {
            $observer->emit($event);
        }
    }
}
