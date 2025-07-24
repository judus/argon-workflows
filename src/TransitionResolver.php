<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use RuntimeException;

/**
 * Class TransitionResolver
 * Resolves the next state transition based on signals.
 */
final readonly class TransitionResolver
{
    /**
     * Resolves the next state for the given context and signals.
     *
     * @param ContextInterface $context
     * @param array<string, mixed> $signals
     * @param WorkflowDefinition $workflow
     * @return string
     * @throws RuntimeException When no valid transition can be resolved.
     */
    public function resolve(
        ContextInterface $context,
        array $signals,
        WorkflowDefinition $workflow
    ): string {
        foreach ($workflow->signalTransitions as $signal => $targetState) {
            if ($signals[$signal] ?? false) {
                return $targetState;
            }
        }

        return $workflow->staticTransitions[$context->getState()] ??
            throw new RuntimeException(
                "No valid transition for state: {$context->getState()}"
            );
    }
}
