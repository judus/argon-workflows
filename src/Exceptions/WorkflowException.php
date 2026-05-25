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

    public static function forTransitionNotResolvable(string $state): self
    {
        return new self("No valid transition for state: {$state}");
    }

    public static function forInvalidSignalKey(string $type): self
    {
        return new self("Signal names must be strings, {$type} given");
    }

    public static function forInvalidSignalValue(string $signal, string $type): self
    {
        return new self("Signal '{$signal}' must resolve to a boolean, {$type} given");
    }

    public static function forContextTypeMismatch(
        string $expected,
        string $actual,
        string $state
    ): self {
        return new self(
            "Handler for state {$state} returned context of type {$actual}; " .
            "expected {$expected}"
        );
    }

    public static function forInvalidMaxSteps(int $maxSteps): self
    {
        return new self("Workflow max steps must be greater than zero, {$maxSteps} given");
    }

    public static function forMaxStepsExceeded(string $workflowId, int $maxSteps, string $state): self
    {
        return new self(
            "Workflow '{$workflowId}' exceeded the configured max step limit of {$maxSteps} " .
            "while entering state '{$state}'"
        );
    }
}
