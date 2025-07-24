<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Contracts;

/**
 * Interface ContextInterface
 * Describes a context within workflows.
 */
interface ContextInterface
{
    /**
     * Gets the current state of the context.
     *
     * @return string
     */
    public function getState(): string;

    /**
     * Checks if the context is complete.
     *
     * @return bool
     */
    public function isComplete(): bool;

    /**
     * Returns a new context with updated state.
     *
     * @param string $state
     * @return ContextInterface
     */
    public function withState(string $state): self;
}
