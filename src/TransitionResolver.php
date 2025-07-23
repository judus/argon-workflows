<?php

declare(strict_types=1);

namespace Alix\Workflows;

use Alix\Workflows\Contracts\ContextInterface;
use Alix\Workflows\Contracts\StateInterface;
use RuntimeException;

final readonly class TransitionResolver
{
    /**
     * @param array<string, mixed> $signals
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
