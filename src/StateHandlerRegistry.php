<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\StateHandlerInterface;
use Maduser\Argon\Workflows\Exceptions\WorkflowException;
use RuntimeException;

/**
 * Class StateHandlerRegistry
 * Manages registration and retrieval of state handlers.
 */
final class StateHandlerRegistry
{
    /** @var array<string, StateHandlerInterface>  */
    private array $handlers = [];

    /**
     * Registers a handler for a specific state.
     *
     * @param string $state
     * @param StateHandlerInterface $handler
     * @throws RuntimeException When handler already exists for state.
     */
    public function register(string $state, StateHandlerInterface $handler): void
    {
        if (isset($this->handlers[$state])) {
            throw WorkflowException::forHandlerAlreadyRegistered($state);
        }

        $this->handlers[$state] = $handler;
    }

    /**
     * Retrieves the handler for a given state.
     *
     * @param string $state
     * @return StateHandlerInterface
     * @throws RuntimeException When no handler is registered for state.
     */
    public function get(string $state): StateHandlerInterface
    {
        if (!isset($this->handlers[$state])) {
            throw WorkflowException::forHandlerNotFound($state);
        }

        return $this->handlers[$state];
    }
}
