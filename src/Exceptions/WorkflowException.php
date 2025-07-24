<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Exceptions;

use RuntimeException;

final class WorkflowException extends RuntimeException
{
    public static function forHandlerAlreadyRegistered(string $state): self
    {
        return new self("Handler already registered for state: $state");
    }

    public static function forHandlerNotFound(string $state): self
    {
        return new self("No handler registered for state: $state");
    }

    public static function forWorkflowAlreadyRegistered(string $name): self
    {
        return new self("Workflow '$name' already registered.");
    }

    public static function forWorkflowNotRegistered(string $name): self
    {
        return new self("No workflow registered for '$name'");
    }
}
