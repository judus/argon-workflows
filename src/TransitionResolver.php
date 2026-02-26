<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Exceptions\WorkflowException;

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
     * @param array<string, bool> $signals
     * @param WorkflowDefinition $workflow
     * @return string
     * @throws WorkflowException When no valid transition can be resolved.
     */
    public function resolve(
        ContextInterface $context,
        array $signals,
        WorkflowDefinition $workflow
    ): string {
        $currentState = $context->getState();

        foreach ($workflow->signalTransitions as $signal => $targetState) {
            if (($signals[$signal] ?? false) === true) {
                return $targetState;
            }
        }

        return $workflow->staticTransitions[$currentState] ??
            throw WorkflowException::forTransitionNotResolvable($currentState);
    }
}
