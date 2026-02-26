<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

/**
 * Class ExecutionEvent
 * Represents a workflow execution event.
 */
final readonly class ExecutionEvent
{
    public const TYPE_RUN_STARTED = 'run.started';
    public const TYPE_RUN_FINISHED = 'run.finished';
    public const TYPE_RUN_FAILED = 'run.failed';
    public const TYPE_STEP_STARTED = 'step.started';
    public const TYPE_STEP_FINISHED = 'step.finished';
    public const TYPE_STEP_FAILED = 'step.failed';
    public const TYPE_TRANSITION_TAKEN = 'transition.taken';

    /**
     * @param string $type
     * @param string $runId
     * @param string $workflowId
     * @param string $state
     * @param int $timestampMs
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $type,
        public string $runId,
        public string $workflowId,
        public string $state,
        public int $timestampMs,
        public array $meta = [],
    ) {
    }
}
