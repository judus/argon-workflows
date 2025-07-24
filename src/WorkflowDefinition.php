<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

/**
 * Class WorkflowDefinition
 * Represents the definition of a workflow including transitions.
 */
final readonly class WorkflowDefinition
{
    /**
     * @param array<string, string> $staticTransitions
     * @param array<string, string> $signalTransitions
     */
    public function __construct(
        public array $staticTransitions,
        public array $signalTransitions,
    ) {
    }
}
