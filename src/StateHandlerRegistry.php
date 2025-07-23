<?php

declare(strict_types=1);

namespace Alix\Workflows;

use Alix\Workflows\Contracts\StateHandlerInterface;
use Alix\Workflows\Contracts\StateInterface;
use RuntimeException;

class StateHandlerRegistry
{
    /** @var array<string, StateHandlerInterface> */
    private array $handlers = [];

    public function register(string $state, StateHandlerInterface $handler): void
    {
        if (isset($this->handlers[$state])) {
            throw new RuntimeException("Handler already registered for state: $state");
        }

        $this->handlers[$state] = $handler;
    }

    public function get(string $state): StateHandlerInterface
    {
        if (!isset($this->handlers[$state])) {
            throw new RuntimeException("No handler registered for state: $state");
        }

        return $this->handlers[$state];
    }
}
