<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Contracts;

use Maduser\Argon\Workflows\ExecutionEvent;

/**
 * Interface ExecutionObserverInterface
 * Receives workflow execution events.
 */
interface ExecutionObserverInterface
{
    public function emit(ExecutionEvent $event): void;
}
