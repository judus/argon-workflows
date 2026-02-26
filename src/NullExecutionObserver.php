<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\ExecutionObserverInterface;

/**
 * Class NullExecutionObserver
 * Default no-op observer.
 */
final class NullExecutionObserver implements ExecutionObserverInterface
{
    public function emit(ExecutionEvent $event): void
    {
        // Intentionally left blank.
    }
}
