<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Contracts;

use Maduser\Argon\Workflows\HandlerResult;

/**
 * Interface StateHandlerInterface
 * Provides a contract for handling states.
 */
interface StateHandlerInterface
{
    /**
     * Handles a given context.
     *
     * @param ContextInterface $context
     * @return HandlerResult
     */
    public function handle(ContextInterface $context): HandlerResult;
}
